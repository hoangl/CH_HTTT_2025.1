import numpy as np
import matplotlib.pyplot as plt
from scipy.io import loadmat
from sklearn.svm import SVC
from sklearn.metrics import accuracy_score
from sklearn.model_selection import train_test_split
from PIL import Image

def auto_detect_and_load_data():
    """Load .mat files with auto-detection """
    try:
        # Load positive samples
        pos_array = loadmat('possamples.mat')['possamples']

        # Load negative samples
        neg_array = loadmat('negsamples.mat')['negsamples']

        print(f"Pos data shape: {pos_array.shape}")
        print(f"Neg data shape: {neg_array.shape}")

        # Auto-detect image size từ total elements
        total_pos = pos_array.size
        possible_sizes = [(48, 48), (64, 64), (32, 32), (36, 36)]

        img_size = None
        for h, w in possible_sizes:
            if total_pos % (h * w) == 0:
                n_images = total_pos // (h * w)
                print(f"✅ {h}x{w} → {n_images} positive images")
                img_size = (h, w)
                break

        if img_size is None:
            print("Cannot detect image size, using 48x48")
            img_size = (48, 48)

        # Reshape data
        pixels_per_img = img_size[0] * img_size[1]
        n_pos = pos_array.size // pixels_per_img
        n_neg = neg_array.size // pixels_per_img

        # Fix: Flatten and reshape correctly
        pos_imgs = pos_array.flatten()[:n_pos * pixels_per_img].reshape(n_pos, img_size[0], img_size[1])
        neg_imgs = neg_array.flatten()[:n_neg * pixels_per_img].reshape(n_neg, img_size[0], img_size[1])


        # Normalize to [0,1]
        pos_imgs = pos_imgs.astype(np.float32) / 255.0 if pos_imgs.max() > 1 else pos_imgs.astype(np.float32)
        neg_imgs = neg_imgs.astype(np.float32) / 255.0 if neg_imgs.max() > 1 else neg_imgs.astype(np.float32)

        print(f"Loaded {n_pos} positive + {n_neg} negative samples ({img_size})")
        return pos_imgs, neg_imgs, img_size

    except Exception as e:
        print(f"Error: {e}")
        print("Creating dummy data...")
        return create_dummy_data()

def create_dummy_data():
    """Fallback dummy data"""
    img_size = (48, 48)
    n_pos, n_neg = 200, 200

    pos_imgs = []
    for i in range(n_pos):
        img = np.random.rand(48, 48) * 0.3
        # Add face pattern
        center = 24
        y, x = np.ogrid[:48, :48]
        mask = ((x - center)**2 / 15**2 + (y - center)**2 / 18**2) <= 1
        img[mask] += 0.4
        # Eyes and mouth
        img[18:21, 14:17] = img[18:21, 31:34] = 0.2  # Eyes
        img[30:32, 20:28] = 0.3  # Mouth
        pos_imgs.append(img)

    neg_imgs = [np.random.rand(48, 48) for _ in range(n_neg)]
    return np.array(pos_imgs), np.array(neg_imgs), img_size

# Main pipeline
def run_face_detection():
    print("Face Detection Pipeline")
    print("="*50)

    # 1. Load data
    pos_imgs, neg_imgs, img_size = auto_detect_and_load_data()

    # 2. Prepare training data
    X = np.concatenate([
        pos_imgs.reshape(len(pos_imgs), -1),
        neg_imgs.reshape(len(neg_imgs), -1)
    ], axis=0)

    y = np.concatenate([
        np.ones(len(pos_imgs)),
        np.zeros(len(neg_imgs))
    ])

    print(f"Training data: {X.shape}, Labels: {y.shape}")

    # 3. Split and normalize
    X_tr, X_val, y_tr, y_val = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

    mean, std = X_tr.mean(axis=0), X_tr.std(axis=0)
    std[std == 0] = 1

    X_tr_norm = (X_tr - mean) / std
    X_val_norm = (X_val - mean) / std

    # 4. Train SVM with different C values
    print("\nTraining SVM...")
    C_values = [0.001, 0.01, 0.1, 1, 10, 100]
    best_acc, best_clf = 0, None

    for C in C_values:
        clf = SVC(kernel='linear', C=C, random_state=42).fit(X_tr_norm, y_tr)
        acc = accuracy_score(y_val, clf.predict(X_val_norm))
        print(f"C={C:<5} Val Acc={acc:.3f}")
        if acc > best_acc:
            best_acc, best_clf = acc, clf

    # 5. Extract hyperplane
    W = (best_clf.dual_coef_[0][:, None] * best_clf.support_vectors_).sum(axis=0)
    b = best_clf.intercept_

    print(f"\nBest validation accuracy: {best_acc:.3f}")

    # 6. Visualize hyperplane
    plt.figure(figsize=(6, 6))
    plt.imshow(W.reshape(img_size), cmap='RdBu_r')
    plt.title('SVM Hyperplane (W) - Looks like average face!')
    plt.colorbar()
    plt.axis('off')
    plt.show()

    return W, b, mean, std, img_size

# 7. Face detection functions
def detect_faces_in_image(image_path, W, b, mean, std, img_size, confidence_threshold=0.5, iou_threshold=0.3):
    """Detect faces trong ảnh test"""
    try:
        # Load image
        img = np.array(Image.open(image_path).convert('L')) / 255.0
        h, w = img.shape

        detections = []
        step = 8
        win_h, win_w = img_size


        # Sliding window
        for y in range(0, h - win_h + 1, step):
            for x in range(0, w - win_w + 1, step):
                patch = img[y:y+win_h, x:x+win_w]
                if patch.shape == img_size:
                    # Normalize and classify
                    patch_norm = (patch.flatten() - mean) / std
                    score = patch_norm.dot(W) + b

                    if score > confidence_threshold:  # Use the confidence threshold
                        detections.append({
                            'x': x, 'y': y, 'w': win_w, 'h': win_h, 'score': score
                        })

        # Improved NMS (Non-Maximum Suppression)
        def iou(box1, box2):
            x_overlap = max(0, min(box1['x'] + box1['w'], box2['x'] + box2['w']) - max(box1['x'], box2['x']))
            y_overlap = max(0, min(box1['y'] + box1['h'], box2['y'] + box2['h']) - max(box1['y'], box2['y']))
            intersection = x_overlap * y_overlap
            area1 = box1['w'] * box1['h']
            area2 = box2['w'] * box2['h']
            union = area1 + area2 - intersection
            return intersection / union if union > 0 else 0

        detections.sort(key=lambda d: d['score'], reverse=True)
        final_detections = []


        while detections:
            best_det = detections.pop(0)
            final_detections.append(best_det)
            detections = [det for det in detections if iou(best_det, det) < iou_threshold]

        # Visualize
        plt.figure(figsize=(10, 8))
        plt.imshow(img, cmap='gray')
        for det in final_detections:
            rect = plt.Rectangle((det['x'], det['y']), det['w'], det['h'],
                               fill=False, color='red', linewidth=2)
            plt.gca().add_patch(rect)
        plt.title(f"{image_path}: {len(final_detections)} faces detected with confidence_threshold={confidence_threshold} and iou_threshold={iou_threshold}")
        plt.axis('off')
        plt.show()

    except Exception as e:
        print(f"Error processing {image_path}: {e}")

# Run complete pipeline
W, b, mean, std, img_size = run_face_detection()

# Test trên 4 ảnh
# ('img1.jpg', 0.0, 0.0),  # img1.jpg: cannot find any values of confidence_threshold and iou_threshold to match face detections
# ('img2.jpg', 0.5, 0.3),  # img2.jpg: adjust confidence_threshold and iou_threshold
# ('img3.jpg', 0.5, 0.3),  # img3.jpg: (.0,.1),(.1,.1),(.2,.1),(.3,.1),(.4,.1),(.5,.1),(.6,.1),(.7,.1),(.8,.1),(.9,.1)
# ('img4.jpg', 0.5, 0.3),  # img4.jpg: cannot find any values of confidence_threshold and iou_threshold to match face detections
# ('img5.jpg', 0.5, 0.3)   # img5.jpg: adjust confidence_threshold and iou_threshold
test_images = [
    ('img3.jpg', 0.0, 0.0),
    ('img3.jpg', 0.0, 0.1),
    ('img3.jpg', 0.0, 0.2),
    ('img3.jpg', 0.0, 0.3),
    ('img3.jpg', 0.0, 0.4),
    ('img3.jpg', 0.0, 0.5),
    ('img3.jpg', 0.0, 0.6),
    ('img3.jpg', 0.0, 0.7),
    ('img3.jpg', 0.0, 0.7),
    ('img3.jpg', 0.0, 0.8),
    ('img3.jpg', 0.0, 0.9),
    ('img3.jpg', 0.1, 0.0),
    ('img3.jpg', 0.1, 0.1),
    ('img3.jpg', 0.1, 0.2),
    ('img3.jpg', 0.1, 0.3),
    ('img3.jpg', 0.1, 0.4),
    ('img3.jpg', 0.1, 0.5),
    ('img3.jpg', 0.1, 0.6),
    ('img3.jpg', 0.1, 0.7),
    ('img3.jpg', 0.1, 0.7),
    ('img3.jpg', 0.1, 0.8),
    ('img3.jpg', 0.1, 0.9),
    ('img3.jpg', 0.2, 0.0),
    ('img3.jpg', 0.2, 0.1),
    ('img3.jpg', 0.2, 0.2),
    ('img3.jpg', 0.2, 0.3),
    ('img3.jpg', 0.2, 0.4),
    ('img3.jpg', 0.2, 0.5),
    ('img3.jpg', 0.2, 0.6),
    ('img3.jpg', 0.2, 0.7),
    ('img3.jpg', 0.2, 0.7),
    ('img3.jpg', 0.2, 0.8),
    ('img3.jpg', 0.2, 0.9),
    ('img3.jpg', 0.3, 0.0),
    ('img3.jpg', 0.3, 0.1),
    ('img3.jpg', 0.3, 0.2),
    ('img3.jpg', 0.3, 0.3),
    ('img3.jpg', 0.3, 0.4),
    ('img3.jpg', 0.3, 0.5),
    ('img3.jpg', 0.3, 0.6),
    ('img3.jpg', 0.3, 0.7),
    ('img3.jpg', 0.3, 0.7),
    ('img3.jpg', 0.3, 0.8),
    ('img3.jpg', 0.3, 0.9),
    ('img3.jpg', 0.4, 0.0),
    ('img3.jpg', 0.4, 0.1),
    ('img3.jpg', 0.4, 0.2),
    ('img3.jpg', 0.4, 0.3),
    ('img3.jpg', 0.4, 0.4),
    ('img3.jpg', 0.4, 0.5),
    ('img3.jpg', 0.4, 0.6),
    ('img3.jpg', 0.4, 0.7),
    ('img3.jpg', 0.4, 0.7),
    ('img3.jpg', 0.4, 0.8),
    ('img3.jpg', 0.4, 0.9),
    ('img3.jpg', 0.5, 0.0),
    ('img3.jpg', 0.5, 0.1),
    ('img3.jpg', 0.5, 0.2),
    ('img3.jpg', 0.5, 0.3),
    ('img3.jpg', 0.5, 0.4),
    ('img3.jpg', 0.5, 0.5),
    ('img3.jpg', 0.5, 0.6),
    ('img3.jpg', 0.5, 0.7),
    ('img3.jpg', 0.5, 0.7),
    ('img3.jpg', 0.5, 0.8),
    ('img3.jpg', 0.5, 0.9),
    ('img3.jpg', 0.6, 0.0),
    ('img3.jpg', 0.6, 0.1),
    ('img3.jpg', 0.6, 0.2),
    ('img3.jpg', 0.6, 0.3),
    ('img3.jpg', 0.6, 0.4),
    ('img3.jpg', 0.6, 0.5),
    ('img3.jpg', 0.6, 0.6),
    ('img3.jpg', 0.6, 0.7),
    ('img3.jpg', 0.6, 0.7),
    ('img3.jpg', 0.6, 0.8),
    ('img3.jpg', 0.6, 0.9),
    ('img3.jpg', 0.7, 0.0),
    ('img3.jpg', 0.7, 0.1),
    ('img3.jpg', 0.7, 0.2),
    ('img3.jpg', 0.7, 0.3),
    ('img3.jpg', 0.7, 0.4),
    ('img3.jpg', 0.7, 0.5),
    ('img3.jpg', 0.7, 0.6),
    ('img3.jpg', 0.7, 0.7),
    ('img3.jpg', 0.7, 0.7),
    ('img3.jpg', 0.7, 0.8),
    ('img3.jpg', 0.7, 0.9),
    ('img3.jpg', 0.8, 0.0),
    ('img3.jpg', 0.8, 0.1),
    ('img3.jpg', 0.8, 0.2),
    ('img3.jpg', 0.8, 0.3),
    ('img3.jpg', 0.8, 0.4),
    ('img3.jpg', 0.8, 0.5),
    ('img3.jpg', 0.8, 0.6),
    ('img3.jpg', 0.8, 0.7),
    ('img3.jpg', 0.8, 0.7),
    ('img3.jpg', 0.8, 0.8),
    ('img3.jpg', 0.8, 0.9),
    ('img3.jpg', 0.9, 0.0),
    ('img3.jpg', 0.9, 0.1),
    ('img3.jpg', 0.9, 0.2),
    ('img3.jpg', 0.9, 0.3),
    ('img3.jpg', 0.9, 0.4),
    ('img3.jpg', 0.9, 0.5),
    ('img3.jpg', 0.9, 0.6),
    ('img3.jpg', 0.9, 0.7),
    ('img3.jpg', 0.9, 0.7),
    ('img3.jpg', 0.9, 0.8),
    ('img3.jpg', 0.9, 0.9)
]

print("\nTesting on images...")
for img_path, conf_thresh, iou_thresh in test_images:
    detect_faces_in_image(img_path, W, b, mean, std, img_size, confidence_threshold=conf_thresh, iou_threshold=iou_thresh)

print("\nFace Detection completed!")