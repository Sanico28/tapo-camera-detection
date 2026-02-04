# ======================================
# Ultralytics YOLO + EasyOCR
# Automatic Number Plate Recognition
# ======================================

import cv2
import torch
import easyocr
import numpy as np
from pathlib import Path
from ultralytics import YOLO
from ultralytics.utils.plotting import Annotator, colors


class ANPR:
    """Automatic Number Plate Recognition using Ultralytics YOLO and EasyOCR.

    This class handles license plate detection using a YOLO model and text extraction
    using EasyOCR. It supports both image and video streams for real-time inference.

    Attributes:
        model (YOLO): The YOLO model for license plate detection.
        reader (easyocr.Reader): The OCR reader instance for text recognition.
        device (torch.device): Computation device (CPU or CUDA).
    """

    def __init__(self, model_path: str = "anpr_best.pt"):
        """Initializes the ANPR system."""
        self.device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
        # Resolve path relative to script directory
        script_dir = Path(__file__).resolve().parent
        model_file = Path(model_path)
        if not model_file.is_absolute():
            model_file = script_dir / model_path
        if not model_file.exists():
            raise FileNotFoundError(f"Model not found: {model_file}")
        self.model = YOLO(str(model_file))
        self.reader = easyocr.Reader(["en"], gpu=torch.cuda.is_available())

    def detect_plates(self, im0: np.ndarray):
        """Detects license plates in an image."""
        results = self.model.predict(im0, verbose=False)
        boxes = results[0].boxes.xyxy.cpu().numpy() if results and results[0].boxes is not None else []
        return boxes

    def extract_text(self, im0: np.ndarray, bbox: np.ndarray):
        """Performs OCR on the cropped license plate region."""
        x1, y1, x2, y2 = map(int, bbox)
        roi = im0[y1:y2, x1:x2]
        if roi.size == 0:
            return ""
        gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
        text = self.reader.readtext(gray, detail=0, paragraph=True)
        return " ".join(text).strip() if text else ""

    def infer_video(self, source: str = 0, output_path: str = None, display: bool = True):
        """Performs real-time ANPR on a video stream."""
        cap = cv2.VideoCapture(source)
        if not cap.isOpened():
            raise ValueError(f"Cannot open video source: {source}")

        width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        fps = cap.get(cv2.CAP_PROP_FPS) or 30

        writer = None
        if output_path:
            fourcc = cv2.VideoWriter_fourcc(*"mp4v")
            writer = cv2.VideoWriter(output_path, fourcc, fps, (width, height))

        print("🚀 Starting ANPR video inference... Press 'q' to quit.")

        while True:
            ret, im0 = cap.read()
            if not ret:
                break

            boxes = self.detect_plates(im0)
            ann = Annotator(im0, line_width=4)
            for bbox in boxes:
                text = self.extract_text(im0, bbox)
                ann.box_label(bbox, label=text, color=colors(17, True))

            if display:
                cv2.imshow("ANPR (Press 'q' to exit)", im0)
            if writer:
                writer.write(im0)

            if cv2.waitKey(1) & 0xFF == ord("q"):
                break

        cap.release()
        if writer:
            writer.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="ANPR - Automatic Number Plate Recognition")
    parser.add_argument("--source", "-s", default="0", help="Video source (file path or 0 for webcam)")
    parser.add_argument("--output", "-o", default=None, help="Output video path")
    parser.add_argument("--model", "-m", default="anpr_best.pt", help="Path to YOLO license plate model")
    parser.add_argument("--no-display", action="store_true", help="Do not show display window")
    args = parser.parse_args()

    anpr = ANPR(model_path=args.model)
    source = int(args.source) if args.source.isdigit() else args.source
    anpr.infer_video(
        source=source,
        output_path=args.output,
        display=not args.no_display,
    )
