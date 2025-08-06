from sklearn.naive_bayes import GaussianNB

# --- 1. Data Representation and Mapping ---
age_mapping = {'<=30': 0, '31...40': 1, '>40': 2}
income_mapping = {'low': 0, 'medium': 1, 'high': 2}
student_mapping = {'no': 0, 'yes': 1}
credit_rating_mapping = {'fair': 0, 'excellent': 1}
buys_computer_mapping = {'no': 0, 'yes': 1}
buys_computer_inverse_mapping = {0: 'no', 1: 'yes'} # For displaying results

# --- 2. Training Data (X_train, Y_train) ---
training_data_raw = [
    # age, income, student, credit_rating, buys_computer
    ['<=30', 'high', 'no', 'fair', 'no'],
    ['<=30', 'high', 'no', 'excellent', 'no'],
    ['31...40', 'high', 'no', 'fair', 'yes'],
    ['>40', 'medium', 'no', 'fair', 'yes'],
    ['>40', 'low', 'yes', 'fair', 'yes'],
    ['>40', 'low', 'yes', 'excellent', 'no'],
    ['31...40', 'low', 'yes', 'excellent', 'yes'],
    ['<=30', 'medium', 'no', 'fair', 'no'],
    ['<=30', 'low', 'yes', 'fair', 'yes'],
    ['>40', 'medium', 'yes', 'fair', 'yes'],
    ['<=30', 'medium', 'yes', 'excellent', 'yes'],
    ['31...40', 'medium', 'no', 'excellent', 'yes'],
    ['31...40', 'high', 'yes', 'fair', 'yes'],
    ['>40', 'medium', 'no', 'excellent', 'no']
]

X_train = []
Y_train = []
for row in training_data_raw:
    age_val = age_mapping[row[0]]
    income_val = income_mapping[row[1]]
    student_val = student_mapping[row[2]]
    credit_rating_val = credit_rating_mapping[row[3]]
    buys_computer_val = buys_computer_mapping[row[4]]

    X_train.append([age_val, income_val, student_val, credit_rating_val])
    Y_train.append(buys_computer_val)

# --- 3. Build and Train Naive Bayes ---
gnb = GaussianNB()
gnb.fit(X_train, Y_train)

# --- 4. New data ---
new_example_raw = ['<=30', 'medium', 'yes', 'fair']

# Convert new data to encoded numerical format for prediction
age_val = age_mapping[new_example_raw[0]]
income_val = income_mapping[new_example_raw[1]]
student_val = student_mapping[new_example_raw[2]]
credit_rating_val = credit_rating_mapping[new_example_raw[3]]

X_new = [[age_val, income_val, student_val, credit_rating_val]]

# --- 5. Make prediction and Print result ---
y_pred_encoded = gnb.predict(X_new)
y_pred = buys_computer_inverse_mapping[y_pred_encoded[0]]

print(f"Predict the class of the following new example using Naive Bayes Classification Age<=30, income=medium, "
      f"student=yes, credit-rating=fair is: {y_pred}")