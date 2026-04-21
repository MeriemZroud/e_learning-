<?php

namespace App\Controller;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Entity\ForumReview;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/student/blog')]
class StudentForumController extends AbstractController
{
    #[Route('', name: 'app_student_forum_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = trim((string) $request->query->get('search', ''));

        $qb = $entityManager->createQueryBuilder()
            ->select('p')
            ->from(ForumPost::class, 'p');

        if ($search !== '') {
            $or = $qb->expr()->orX(
                $qb->expr()->like('LOWER(p.title)', 'LOWER(:search)'),
                $qb->expr()->like('LOWER(p.content)', 'LOWER(:search)')
            );
            $qb->andWhere($or);
            $qb->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('p.created_at', 'DESC');

        $posts = $qb->getQuery()->getResult();

        return $this->render('dashboard/student_forum.html.twig', [
            'posts' => $posts,
            'search' => $search,
        ]);
    }

    #[Route('/post', name: 'app_student_forum_post_create', methods: ['POST'])]
    public function createPost(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_post_create', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $title = trim((string) $request->request->get('title', ''));
        $content = trim((string) $request->request->get('content', ''));

        if ($title === '' || $content === '') {
            $this->addFlash('error', 'Title and content are required.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post = new ForumPost();
        $post->setTitle($title);
        $post->setContent($content);
        $post->setUser($user);
        $post->setCreatedAt(new \DateTime());

        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post created successfully.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/post/{id}/edit', name: 'app_student_forum_post_edit', methods: ['POST'])]
    public function editPost(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_post_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post = $entityManager->getRepository(ForumPost::class)->find($id);
        if (!$post instanceof ForumPost) {
            $this->addFlash('error', 'Post not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        if ($post->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only edit your own posts.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $title = trim((string) $request->request->get('title', ''));
        $content = trim((string) $request->request->get('content', ''));

        if ($title === '' || $content === '') {
            $this->addFlash('error', 'Title and content are required.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post->setTitle($title);
        $post->setContent($content);
        $entityManager->flush();

        $this->addFlash('success', 'Post updated.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/post/{id}/delete', name: 'app_student_forum_post_delete', methods: ['POST'])]
    public function deletePost(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_post_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post = $entityManager->getRepository(ForumPost::class)->find($id);
        if (!$post instanceof ForumPost) {
            $this->addFlash('error', 'Post not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        if ($post->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only delete your own posts.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        foreach ($post->getForumComments() as $comment) {
            $this->removeCommentTree($entityManager, $comment);
        }

        if ($post->getForumReview() instanceof ForumReview) {
            $entityManager->remove($post->getForumReview());
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post deleted.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/post/{id}/comment', name: 'app_student_forum_comment_create', methods: ['POST'])]
    public function addComment(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_comment_create_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post = $entityManager->getRepository(ForumPost::class)->find($id);
        if (!$post instanceof ForumPost) {
            $this->addFlash('error', 'Post not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content === '') {
            $this->addFlash('error', 'Comment content is required.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $parent = null;
        $parentId = trim((string) $request->request->get('parent_id', ''));
        if ($parentId !== '') {
            $parentCandidate = $entityManager->getRepository(ForumComment::class)->find((int) $parentId);
            if ($parentCandidate instanceof ForumComment && $parentCandidate->getForumPost()?->getId() === $post->getId()) {
                $parent = $parentCandidate;
            }
        }

        $comment = new ForumComment();
        $comment->setForumPost($post);
        $comment->setForumComment($parent);
        $comment->setUser($user);
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTime());

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', $parent instanceof ForumComment ? 'Reply added.' : 'Comment added.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/comment/{id}/delete', name: 'app_student_forum_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_comment_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $comment = $entityManager->getRepository(ForumComment::class)->find($id);
        if (!$comment instanceof ForumComment) {
            $this->addFlash('error', 'Comment not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        if ($comment->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only delete your own comments.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $this->removeCommentTree($entityManager, $comment);
        $entityManager->flush();

        $this->addFlash('success', 'Comment deleted.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/comment/{id}/edit', name: 'app_student_forum_comment_edit', methods: ['POST'])]
    public function editComment(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_comment_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $comment = $entityManager->getRepository(ForumComment::class)->find($id);
        if (!$comment instanceof ForumComment) {
            $this->addFlash('error', 'Comment not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        if ($comment->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only edit your own comments.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content === '') {
            $this->addFlash('error', 'Comment content is required.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $comment->setContent($content);
        $entityManager->flush();

        $this->addFlash('success', 'Comment updated.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/post/{id}/rate', name: 'app_student_forum_post_rate', methods: ['POST'])]
    public function ratePost(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_post_rate_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post = $entityManager->getRepository(ForumPost::class)->find($id);
        if (!$post instanceof ForumPost) {
            $this->addFlash('error', 'Post not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $rating = (int) $request->request->get('rating', 0);
        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'Rating must be between 1 and 5.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $review = $post->getForumReview();
        if (!$review instanceof ForumReview) {
            $review = new ForumReview();
            $review->setForumPost($post);
            $entityManager->persist($review);
        } elseif ($review->getUser() instanceof User && $review->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'This post rating belongs to another student.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $review->setUser($user);
        $review->setRating($rating);
        $review->setReviewText(trim((string) $request->request->get('review_text', '')) ?: null);
        $review->setCreatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Rating submitted.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/post/{id}/rate/delete', name: 'app_student_forum_post_rate_delete', methods: ['POST'])]
    public function deleteRating(int $id, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('forum_post_rate_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $post = $entityManager->getRepository(ForumPost::class)->find($id);
        if (!$post instanceof ForumPost) {
            $this->addFlash('error', 'Post not found.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $review = $post->getForumReview();
        if (!$review instanceof ForumReview) {
            $this->addFlash('error', 'No rating to delete.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        if ($review->getUser() instanceof User && $review->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only delete your own rating.');

            return $this->redirectToRoute('app_student_forum_index');
        }

        $entityManager->remove($review);
        $entityManager->flush();

        $this->addFlash('success', 'Rating deleted.');

        return $this->redirectToRoute('app_student_forum_index');
    }

    #[Route('/post/{id}/suggest', name: 'app_student_forum_post_suggest', methods: ['POST'])]
    public function suggestResponse(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if (!$this->isCsrfTokenValid('forum_post_suggest_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid request token'], 403);
        }

        $post = $entityManager->getRepository(ForumPost::class)->find($id);
        if (!$post instanceof ForumPost) {
            return new JsonResponse(['error' => 'Post not found'], 404);
        }

        try {
            $prompt = "Based on this forum post, suggest a helpful and educational response in French. "
                . "Keep it concise (2-4 sentences), practical, and friendly.\n\n";
            $prompt .= "Title: " . $post->getTitle() . "\n";
            $prompt .= "Content: " . $post->getContent();

            $apiKey = trim((string) ($_ENV['HUGGING_FACE_API_KEY'] ?? ''));
            $model = trim((string) ($_ENV['HUGGING_FACE_MODEL'] ?? 'meta-llama/Llama-3.1-8B-Instruct'));

            if ($apiKey === '') {
                return new JsonResponse(['error' => 'HUGGING_FACE_API_KEY is not configured.'], 500);
            }

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
                            'content' => 'You are a helpful academic forum assistant.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 180,
                    'temperature' => 0.7,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $payload = $response->getContent(false);
            $data = json_decode($payload, true);

            if ($statusCode < 400) {
                if (is_array($data)
                    && isset($data['choices'][0]['message']['content'])
                    && is_string($data['choices'][0]['message']['content'])) {
                    $suggestion = trim($data['choices'][0]['message']['content']);
                    return new JsonResponse([
                        'suggestion' => $suggestion,
                        'source' => 'huggingface',
                    ]);
                }
            }

            $fallback = $this->buildLocalSuggestion($post);
            return new JsonResponse([
                'suggestion' => $fallback,
                'source' => 'local',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'suggestion' => $this->buildLocalSuggestion($post),
                'source' => 'local',
            ]);
        }
    }

    private function buildLocalSuggestion(ForumPost $post): string
    {
        $title = trim((string) $post->getTitle());
        $content = trim((string) $post->getContent());
        $normalized = preg_replace('/\s+/', ' ', $content);
        $excerpt = substr((string) $normalized, 0, 180);

        return "Voici une excellente question sur \"{$title}\". "
            . "Je recommande de commencer par clarifier l'objectif principal du post, puis de proposer une reponse en etapes simples avec un exemple pratique. "
            . "Pour ce sujet, tu peux repondre en expliquant d'abord la base, puis en ajoutant un petit exercice concret: {$excerpt}...";
    }

    private function removeCommentTree(EntityManagerInterface $entityManager, ForumComment $comment): void
    {
        foreach ($comment->getForumComments() as $child) {
            $this->removeCommentTree($entityManager, $child);
        }

        $entityManager->remove($comment);
    }

    private function requireStudent(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
