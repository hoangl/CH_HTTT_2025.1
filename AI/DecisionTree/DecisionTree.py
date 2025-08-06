from sklearn import tree
from sklearn.tree import plot_tree
import matplotlib.pyplot as plt

# --- 1. Data Representation and Mapping ---
time_mapping = {'1-2': 0, '2-7': 1, '>7': 2}
gender_mapping = {'m': 0, 'f': 1}
area_mapping = {'urban': 0, 'rural': 1}
risk_mapping = {'low': 0, 'high': 1}
risk_inverse_mapping = {0: 'low', 1: 'high'} # For displaying results

# --- 2. Training Data (X_train, Y_train) ---
training_data_raw = [
    # time, gender, area, risk
    ['1-2', 'm', 'urban', 'low'],
    ['2-7', 'm', 'rural', 'high'],
    ['>7', 'f', 'rural', 'low'],
    ['1-2', 'f', 'rural', 'high'],
    ['>7', 'm', 'rural', 'high'],
    ['1-2', 'm', 'rural', 'high'],
    ['2-7', 'f', 'urban', 'low'],
    ['2-7', 'm', 'urban', 'low']
]

# Convert raw data to encoded numerical format for X and Y
X_train = []
Y_train = []
for row in training_data_raw:
    time_val = time_mapping[row[0]]
    gender_val = gender_mapping[row[1]]
    area_val = area_mapping[row[2]]
    risk_val = risk_mapping[row[3]]

    X_train.append([time_val, gender_val, area_val])
    Y_train.append(risk_val)

# --- 3. Construct a decision tree ---
clf = tree.DecisionTreeClassifier()
# clf = tree.DecisionTreeClassifier(random_state=42) # Added random_state for consistent results
clf.fit(X_train, Y_train)

# --- (b) Draw the tree ---
plt.figure()
plot_tree(clf, filled=True)
plt.show()

# --- 4. New data ---
new_example_raw = [
    # ID, time, gender, area
    ['A', '1-2', 'f', 'rural'],
    ['B', '2-7', 'm', 'urban'],
    ['C', '1-2', 'f', 'urban']
]

# Convert new data to encoded numerical format for prediction
X_new = []
new_example_ids = []
for row in new_example_raw:
    new_example_ids.append(row[0]) # Store ID for display
    time_val = time_mapping[row[1]]
    gender_val = gender_mapping[row[2]]
    area_val = area_mapping[row[3]]
    X_new.append([time_val, gender_val, area_val])

# --- 5. Make predictions and Print results ---
predictions_encoded = clf.predict(X_new)

print("Apply the decision tree to predict the risk class for new example data:")
print("ID | Predicted Risk")
print("---|---------------")
for i, pred_val_encoded in enumerate(predictions_encoded):
    predicted_risk = risk_inverse_mapping[pred_val_encoded]
    print(f"{new_example_ids[i]}  | {predicted_risk}")
