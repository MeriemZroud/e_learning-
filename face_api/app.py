from __future__ import annotations

import base64
import os
from typing import Any

import cv2
import numpy as np
from flask import Flask, jsonify, request

app = Flask(__name__)


CASCADE_PATH = cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
FACE_CASCADE = cv2.CascadeClassifier(CASCADE_PATH)


def _decode_image(image_base64: str):
    payload = image_base64
    if "," in payload:
        payload = payload.split(",", 1)[1]

    try:
        image_bytes = base64.b64decode(payload)
    except Exception as exc:  # noqa: BLE001
        raise ValueError("Invalid base64 payload") from exc

    np_buffer = np.frombuffer(image_bytes, dtype=np.uint8)
    image = cv2.imdecode(np_buffer, cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError("Invalid image payload")

    return image


def _extract_face(gray_image: np.ndarray) -> np.ndarray:
    faces = FACE_CASCADE.detectMultiScale(gray_image, scaleFactor=1.1, minNeighbors=5, minSize=(80, 80))
    if len(faces) != 1:
        raise ValueError(f"image must contain exactly one face (found {len(faces)})")

    x, y, w, h = faces[0]
    face = gray_image[y:y + h, x:x + w]
    face = cv2.resize(face, (160, 160), interpolation=cv2.INTER_AREA)
    face = cv2.equalizeHist(face)

    return face


def _prepare_for_matching(face: np.ndarray) -> np.ndarray:
    face = cv2.GaussianBlur(face, (3, 3), 0)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    return clahe.apply(face)


def _orb_distance(face_a: np.ndarray, face_b: np.ndarray) -> float:
    orb = cv2.ORB_create(nfeatures=500)
    keypoints_a, descriptors_a = orb.detectAndCompute(face_a, None)
    keypoints_b, descriptors_b = orb.detectAndCompute(face_b, None)

    if descriptors_a is None or descriptors_b is None or len(keypoints_a) < 8 or len(keypoints_b) < 8:
        # No rich texture points found -> treat as non-match.
        return 1.0

    matcher = cv2.BFMatcher(cv2.NORM_HAMMING, crossCheck=False)
    knn_matches = matcher.knnMatch(descriptors_a, descriptors_b, k=2)

    good_matches = []
    for pair in knn_matches:
        if len(pair) < 2:
            continue
        m, n = pair
        if m.distance < 0.75 * n.distance:
            good_matches.append(m)

    denominator = float(max(len(keypoints_a), len(keypoints_b), 1))
    similarity = len(good_matches) / denominator
    distance = 1.0 - min(1.0, similarity)

    return float(distance)


def _histogram_distance(face_a: np.ndarray, face_b: np.ndarray) -> float:
    hist_a = cv2.calcHist([face_a], [0], None, [64], [0, 256])
    hist_b = cv2.calcHist([face_b], [0], None, [64], [0, 256])
    cv2.normalize(hist_a, hist_a)
    cv2.normalize(hist_b, hist_b)

    correlation = float(cv2.compareHist(hist_a, hist_b, cv2.HISTCMP_CORREL))
    correlation = max(-1.0, min(1.0, correlation))
    return 1.0 - ((correlation + 1.0) / 2.0)


def _pixel_distance(face_a: np.ndarray, face_b: np.ndarray) -> float:
    face_a = face_a.astype(np.float32) / 255.0
    face_b = face_b.astype(np.float32) / 255.0
    diff = np.mean(np.abs(face_a - face_b))
    return float(max(0.0, min(1.0, diff)))


@app.get("/health")
def health() -> Any:
    return jsonify({"ok": True})


@app.post("/verify-face")
def verify_face() -> Any:
    data = request.get_json(silent=True) or {}

    captured_b64 = str(data.get("captured_image_base64", "")).strip()
    reference_b64 = str(data.get("reference_image_base64", "")).strip()
    tolerance = float(data.get("tolerance", 0.7))

    if captured_b64 == "" or reference_b64 == "":
        return jsonify({
            "match": False,
            "reason": "captured_image_base64 and reference_image_base64 are required",
        }), 400

    try:
        captured_image = _decode_image(captured_b64)
        reference_image = _decode_image(reference_b64)

        captured_gray = cv2.cvtColor(captured_image, cv2.COLOR_BGR2GRAY)
        reference_gray = cv2.cvtColor(reference_image, cv2.COLOR_BGR2GRAY)

        captured_face = _prepare_for_matching(_extract_face(captured_gray))
        reference_face = _prepare_for_matching(_extract_face(reference_gray))

        orb_distance = _orb_distance(captured_face, reference_face)
        histogram_distance = _histogram_distance(captured_face, reference_face)
        pixel_distance = _pixel_distance(captured_face, reference_face)
        distance = min(1.0, (0.55 * orb_distance) + (0.25 * histogram_distance) + (0.20 * pixel_distance))
        is_match = bool(distance <= tolerance)
    except ValueError as exc:
        return jsonify({"match": False, "reason": str(exc)}), 422

    return jsonify({
        "match": is_match,
        "distance": round(distance, 6),
        "tolerance": tolerance,
    })


if __name__ == "__main__":
    app.run(
        host=os.environ.get("FACE_API_HOST", "0.0.0.0"),
        port=int(os.environ.get("FACE_API_PORT", "5001")),
        debug=True,
    )
