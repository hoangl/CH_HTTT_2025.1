import numpy as np
from scipy.io import loadmat
from sklearn.model_selection import train_test_split
from sklearn.svm import SVC
from sklearn.metrics import accuracy_score
import joblib
from skimage import io, transform
import matplotlib.pyplot as plt
import matplotlib.patches as patches

# Sử dụng matplotlib để hiển thị W
import matplotlib.pyplot as plt

# Tải dữ liệu từ các file .mat
pos_samples = loadmat('possamples.mat')['possamples']
neg_samples = loadmat('negsamples.mat')['negsamples']

# Chuyển vị mảng để định dạng là (số_mẫu, chiều_cao, chiều_rộng)
pos_samples_transposed = pos_samples.transpose(2, 0, 1)
neg_samples_transposed = neg_samples.transpose(2, 0, 1)

print("Kích thước sau khi chuyển vị của mẫu dương:", pos_samples_transposed.shape)
print("Kích thước sau khi chuyển vị của mẫu âm:", neg_samples_transposed.shape)

# Làm phẳng hình ảnh thành vector
X_pos = pos_samples_transposed.reshape(pos_samples_transposed.shape[0], -1)
X_neg = neg_samples_transposed.reshape(neg_samples_transposed.shape[0], -1)

# Định nghĩa hàm chuẩn hóa trung bình-phương sai
def normalize_data(data):
    mean = np.mean(data, axis=0)
    std = np.std(data, axis=0)
    # Tránh chia cho 0
    std[std == 0] = 1
    return (data - mean) / std

# Chuẩn hóa dữ liệu đã làm phẳng
X_pos_normalized = normalize_data(X_pos)
X_neg_normalized = normalize_data(X_neg)

# Tạo nhãn (1 cho khuôn mặt, 0 cho không phải khuôn mặt)
y_pos = np.ones(X_pos_normalized.shape[0])
y_neg = np.zeros(X_neg_normalized.shape[0])

# Kết hợp dữ liệu và nhãn
X_combined = np.vstack((X_pos_normalized, X_neg_normalized))
y_combined = np.hstack((y_pos, y_neg))

# Chia dữ liệu thành tập huấn luyện và tập xác thực (validation)
X_train, X_val, y_train, y_val = train_test_split(X_combined, y_combined, test_size=0.2, random_state=42, stratify=y_combined)

print("\nKích thước của X_train:", X_train.shape)
print("Kích thước của X_val:", X_val.shape)

best_C = None
best_accuracy = 0
best_svm_model = None
C_values = [0.001, 0.01, 0.1, 1, 10, 100]

for C in C_values:
    # Huấn luyện SVM tuyến tính
    svm_model = SVC(kernel='linear', C=C)
    svm_model.fit(X_train, y_train)

    # Đánh giá trên tập xác thực
    y_pred_val = svm_model.predict(X_val)
    accuracy = accuracy_score(y_val, y_pred_val)

    print(f"Giá trị C: {C}, Độ chính xác trên tập xác thực: {accuracy:.4f}")

    if accuracy > best_accuracy:
        best_accuracy = accuracy
        best_C = C
        best_svm_model = svm_model

print(f"\nGiá trị C tốt nhất là: {best_C} với độ chính xác: {best_accuracy:.4f}")

# Giả sử best_svm_model là mô hình SVM tốt nhất đã được huấn luyện => Save
joblib.dump(best_svm_model, 'best_svm_model.pkl')

# Trích xuất và trực quan hóa siêu mặt phẳng W
# W là vector trọng số của SVM
W = best_svm_model.coef_[0]
# Định hình lại W thành kích thước hình ảnh gốc (24, 24)
W_image = W.reshape(24, 24)

plt.imshow(W_image, cmap='gray')
plt.title(f"Visualizing W for C={best_C}")
plt.savefig('W_visualization.png')

# Tải mô hình từ tệp đã lưu
loaded_svm_model = joblib.load('best_svm_model.pkl')
# Tải ảnh kiểm tra (ví dụ: img1.jpg)
img = io.imread('img1.jpg', as_gray=True)

patch_size = (64, 64)
step_size = 10  # Bước trượt

detections = []
# Vòng lặp quét cửa sổ trượt
for y in range(0, img.shape[0] - patch_size[1], step_size):
    for x in range(0, img.shape[1] - patch_size[0], step_size):
        patch = img[y:y + patch_size[1], x:x + patch_size[0]]

        # Làm phẳng và chuẩn hóa miếng vá
        patch_flat = patch.flatten().reshape(1, -1)
        # Sử dụng hàm normalize_data() từ Phần 1, nhưng phải cẩn thận với việc chuẩn hóa riêng biệt cho mỗi patch
        # Một cách tốt hơn là chuẩn hóa toàn bộ ảnh và trích xuất patch đã chuẩn hóa
        # Tuy nhiên, cách đơn giản hơn là chuẩn hóa cục bộ cho mỗi patch
        patch_normalized = (patch_flat - np.mean(patch_flat)) / (np.std(patch_flat) + 1e-6)

        # Lấy điểm tin cậy
        confidence = loaded_svm_model.decision_function(patch_normalized)[0]

        detections.append({'box': (x, y, patch_size[0], patch_size[1]), 'score': confidence})


# Áp dụng Non-maxima Suppression (NMS)
def nms(detects, cfthresh, nms_threshold):
    """
    Thực hiện Non-Maxima Suppression (NMS) để lọc các hộp giới hạn chồng chéo.

    Args:
        detects (list): Một danh sách các dict, mỗi dict chứa 'box' (hộp giới hạn)
                          và 'score' (điểm tin cậy).
                          Ví dụ: [{'box': (x, y, w, h), 'score': confidence}, ...]
        cfthresh (float): Ngưỡng tin cậy để tiền lọc các hộp giới hạn.
        nms_threshold (float): Ngưỡng Intersection-over-Union (IoU) cho NMS.

    Returns:
        list: Danh sách các hộp giới hạn đã được lọc sau NMS.
    """

    # Tiền lọc các hộp giới hạn dựa trên ngưỡng tin cậy
    detects = [d for d in detects if d['score'] > cfthresh]

    # Nếu không có hộp nào vượt qua ngưỡng, trả về danh sách rỗng
    if not detects:
        return []

    # Trích xuất hộp giới hạn và điểm tin cậy
    boxes = np.array([d['box'] for d in detects])
    scores = np.array([d['score'] for d in detects])

    # Sắp xếp các hộp theo điểm tin cậy giảm dần
    sorted_indices = np.argsort(scores)[::-1]
    boxes = boxes[sorted_indices]
    scores = scores[sorted_indices]

    keep = []
    while len(sorted_indices) > 0:
        # Lấy chỉ số của hộp có điểm cao nhất
        i = sorted_indices[0]
        keep.append(i)

        # Tính toán IoU (Intersection-over-Union) giữa hộp hiện tại và các hộp còn lại

        # Tọa độ hộp hiện tại
        x1_current, y1_current, w_current, h_current = boxes[i]

        # Tọa độ các hộp còn lại
        x1_remaining = boxes[sorted_indices[1:]][:, 0]
        y1_remaining = boxes[sorted_indices[1:]][:, 1]
        w_remaining = boxes[sorted_indices[1:]][:, 2]
        h_remaining = boxes[sorted_indices[1:]][:, 3]

        # Tọa độ góc dưới bên phải
        x2_current = x1_current + w_current
        y2_current = y1_current + h_current
        x2_remaining = x1_remaining + w_remaining
        y2_remaining = y1_remaining + h_remaining

        # Tọa độ vùng giao nhau
        x_overlap = np.maximum(x1_current, x1_remaining)
        y_overlap = np.maximum(y1_current, y1_remaining)
        x_end_overlap = np.minimum(x2_current, x2_remaining)
        y_end_overlap = np.minimum(y2_current, y2_remaining)

        # Tính diện tích vùng giao nhau
        width_overlap = np.maximum(0, x_end_overlap - x_overlap)
        height_overlap = np.maximum(0, y_end_overlap - y_overlap)
        area_overlap = width_overlap * height_overlap

        # Tính diện tích các hộp
        area_current = w_current * h_current
        area_remaining = w_remaining * h_remaining

        # Tính IoU
        union_area = area_current + area_remaining - area_overlap
        iou = area_overlap / (union_area + 1e-6)  # Thêm 1e-6 để tránh chia cho 0

        # Giữ lại các chỉ số của các hộp có IoU thấp hơn ngưỡng
        remaining_indices = np.where(iou <= nms_threshold)[0]
        sorted_indices = sorted_indices[remaining_indices + 1]

    # Trả về các hộp giới hạn đã được lọc
    final_detections = [detects[sorted_indices[i]] for i in keep]

    return final_detections


# Thử nghiệm với các ngưỡng khác nhau
confthresh = 0.8
confthresh_nms = 0.3
final_detections = nms([d for d in detections if d['score'] > confthresh], nms_threshold=confthresh_nms)

# Hiển thị kết quả
fig, ax = plt.subplots(1)
ax.imshow(img, cmap='gray')
for det in final_detections:
    box = det['box']
    rect = patches.Rectangle((box[0], box[1]), box[2], box[3], linewidth=1, edgecolor='r', facecolor='none')
    ax.add_patch(rect)
plt.show()