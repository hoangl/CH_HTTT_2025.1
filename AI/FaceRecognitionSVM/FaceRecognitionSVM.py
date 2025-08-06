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
pos_samples = loadmat('possamples.mat')['possamples']  # Giả sử tên biến là 'positive_samples'
neg_samples = loadmat('negsamples.mat')['negsamples']  # Giả sử tên biến là 'negative_samples'

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
def nms(detections, confthresh=0.5, nms_threshold=0.3):
    # Cần một hàm NMS đầy đủ ở đây
    # Ví dụ: một hàm NMS từ thư viện như `skimage.feature` hoặc tự viết
    # ...
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