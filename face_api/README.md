# Face Recognition API (Flask)

This API verifies if a captured webcam image matches a reference face image.
It uses OpenCV (Haar face detection + ORB feature matching).

## Endpoints

- `GET /health`
- `POST /verify-face`

### Request payload (`POST /verify-face`)

```json
{
  "captured_image_base64": "data:image/jpeg;base64,...",
  "reference_image_base64": "data:image/jpeg;base64,...",
  "tolerance": 0.7
}
```

### Response

```json
{
  "match": true,
  "distance": 0.38421,
  "tolerance": 0.7
}
```

## Run locally

```bash
python -m venv .venv
.venv\\Scripts\\activate
pip install -r requirements.txt
python app.py
```

API URL by default: `http://127.0.0.1:5001/verify-face`

## Notes

- Use one clear face in both images.
- This setup is for development; tune `tolerance` in Symfony env for stricter matching.
- For best stability, keep frontal pose and good lighting.
