<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Entity\Reclamation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReclamationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reclamation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reclamation')
            ->setEntityLabelInPlural('Reclamations')
            ->setDefaultSort(['priority_score' => 'DESC', 'created_at' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $analyzeAi = Action::new('analyzeAi', 'AI')
            ->setIcon('fa fa-wand-magic-sparkles')
            ->setLabel(false)
            ->linkToCrudAction('analyzeAi')
            ->setCssClass('btn btn-sm btn-primary');

        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $analyzeAi)
            ->add(Crud::PAGE_DETAIL, $analyzeAi);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user')->setLabel('User')->hideOnForm();
        yield TextareaField::new('message')->setLabel('Message')->setFormTypeOption('disabled', true);
        yield DateTimeField::new('createdAt')->setLabel('Created at')->hideOnForm();

        yield ChoiceField::new('status')
            ->setLabel('Status')
            ->setChoices([
                'In Progress' => Reclamation::STATUS_IN_PROGRESS,
                'Resolved' => Reclamation::STATUS_RESOLVED,
                'Rejected' => Reclamation::STATUS_REJECTED,
            ])
            ->renderExpanded(false)
            ->renderAsNativeWidget();

        yield ChoiceField::new('priority')
            ->setLabel('Priority')
            ->setChoices([
                'Urgent' => Reclamation::PRIORITY_URGENT,
                'Normal' => Reclamation::PRIORITY_NORMAL,
                'Faible' => Reclamation::PRIORITY_LOW,
            ])
            ->formatValue(static function ($value, $entity): string {
                if (!$entity instanceof Reclamation) {
                    return (string) $value;
                }

                return sprintf('%s (%d/100)', $entity->getPriorityLabel(), $entity->getPriorityScore());
            })
            ->hideOnForm();

        yield IntegerField::new('priorityScore')
            ->setLabel('Priority score')
            ->formatValue(static fn ($value): string => sprintf('%d / 100', (int) $value))
            ->hideOnForm();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Reclamation) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
        $previousStatus = $originalData['status'] ?? $entityInstance->getStatus();

        parent::updateEntity($entityManager, $entityInstance);

        $newStatus = $entityInstance->getStatus();
        if ($previousStatus === $newStatus || !$entityInstance->getUser()) {
            return;
        }

        $notification = new Notification();
        $notification->setUser($entityInstance->getUser());
        $notification->setMessage(sprintf(
            'Your reclamation #%d status changed to %s.',
            $entityInstance->getId(),
            ucfirst(strtolower(str_replace('_', ' ', (string) $newStatus)))
        ));
        $notification->setType('status');
        $notification->setIsRead(false);

        $entityManager->persist($notification);
        $entityManager->flush();
    }

    public function analyzeAi(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $reclamationIndexUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $entityId = $request->query->get('entityId');
        $entity = is_scalar($entityId)
            ? $entityManager->getRepository(Reclamation::class)->find((int) $entityId)
            : null;

        if (!$entity instanceof Reclamation) {
            $this->addFlash('error', 'Invalid reclamation selected for AI analysis.');

            return $this->redirect((string) ($request->query->get('referrer') ?: $reclamationIndexUrl));
        }

        $this->applyPriorityAnalysis($entity);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'AI recommendation updated for reclamation #%d: %s (%d/100).',
                $entity->getId(),
                $entity->getPriorityLabel(),
                $entity->getPriorityScore()
            )
        );

        return $this->redirect((string) ($request->query->get('referrer') ?: $reclamationIndexUrl));
    }

    private function applyPriorityAnalysis(Reclamation $reclamation): void
    {
        $analysis = $this->analyzePriority((string) $reclamation->getMessage());

        $reclamation->setPriorityScore((int) ($analysis['score'] ?? 50));
        $reclamation->setPriority($this->priorityFromScore((int) ($analysis['score'] ?? 50)));
    }

    /**
     * @return array{score:int, source:string, reason?:string}
     */
    private function analyzePriority(string $message): array
    {
        $apiKey = trim((string) ($_ENV['HUGGING_FACE_API_KEY'] ?? ''));
        $model = trim((string) ($_ENV['HUGGING_FACE_MODEL_RECLAMATION'] ?? 'mistralai/Mistral-Nemo-Instruct-2407'));

        if ($apiKey === '' || $model === '') {
            return $this->heuristicPriority($message);
        }

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a support ticket triage assistant for an e-learning platform. Return only JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => sprintf(
                                "Analyse cette réclamation et donne un score de priorité entre 0 et 100.\n" .
                                "Retourne seulement ce JSON: {\"score\":85,\"reason\":\"...\"}.\n" .
                                "Règles: 75-100 = urgent, 40-74 = normal, 0-39 = faible.\n\nRéclamation: %s",
                                $message
                            ),
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 220,
                ],
                'timeout' => 30,
            ]);

            $payload = json_decode($response->getContent(false), true);
            $content = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));
            $json = $this->extractJsonPayload($content);
            $data = json_decode($json, true);

            if (is_array($data) && isset($data['score'])) {
                $score = max(0, min(100, (int) $data['score']));

                return [
                    'score' => $score,
                    'source' => 'huggingface',
                    'reason' => (string) ($data['reason'] ?? ''),
                ];
            }
        } catch (\Throwable) {
        }

        return $this->heuristicPriority($message);
    }

    /**
     * @return array{score:int, source:string, reason:string}
     */
    private function heuristicPriority(string $message): array
    {
        $text = mb_strtolower($message);
        $score = 35;

        $urgentKeywords = [
            'urgent', 'bloqué', 'bloquer', 'mot de passe', 'password', 'accès', 'impossible',
            'erreur 500', 'crash', 'serveur', 'site down', 'compte fermé', 'compte bloqué', 'hack', 'piraté', 'paiement',
        ];
        foreach ($urgentKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 10;
            }
        }

        $normalKeywords = ['problème', 'bug', 'aide', 'corriger', 'modifier', 'question', 'erreur', 'retard'];
        foreach ($normalKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 4;
            }
        }

        $lowKeywords = ['suggestion', 'amélioration', 'idée', 'proposition', 'faible'];
        foreach ($lowKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score -= 12;
            }
        }

        if (mb_strlen($text) > 180) {
            $score += 8;
        }

        if (str_contains($text, '!')) {
            $score += 3;
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'source' => 'heuristic',
            'reason' => 'Fallback local analysis.',
        ];
    }

    private function priorityFromScore(int $score): string
    {
        return match (true) {
            $score >= 75 => Reclamation::PRIORITY_URGENT,
            $score >= 40 => Reclamation::PRIORITY_NORMAL,
            default => Reclamation::PRIORITY_LOW,
        };
    }

    private function extractJsonPayload(string $content): string
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end >= $start) {
            return substr($content, $start, $end - $start + 1);
        }

        return $content;
    }
}
