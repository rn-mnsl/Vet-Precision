<?php
// Validation functions

// Validate registration data
function validateRegistration($data) {
    $errors = [];
    
    // Email
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Password
    if (empty($data['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    // Confirm password
    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm password';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // First name
    if (empty($data['first_name'])) {
        $errors['first_name'] = 'First name is required';
    } elseif (strlen($data['first_name']) > 50) {
        $errors['first_name'] = 'First name too long';
    }
    
    // Last name
    if (empty($data['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    } elseif (strlen($data['last_name']) > 50) {
        $errors['last_name'] = 'Last name too long';
    }
    
    // Phone (optional)
    if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $data['phone'])) {
        $errors['phone'] = 'Invalid phone format';
    }
    
    return $errors;
}

// Validate login data
function validateLogin($data) {
    $errors = [];

    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    }

    if (empty($data['password'])) {
        $errors['password'] = 'Password is required';
    }

    // Check user type
    if (empty($data['user_type']) || !in_array($data['user_type'], ['staff', 'owner'])) {
        $errors['user_type'] = 'Invalid user type';
    }

    return $errors;
}

// Validate pet data
function validatePet($data) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors['name'] = 'Pet name is required';
    }
    
    if (empty($data['species'])) {
        $errors['species'] = 'Species is required';
    }
    
    if (empty($data['owner_id'])) {
        $errors['owner_id'] = 'Owner is required';
    }
    
    if (!empty($data['date_of_birth'])) {
        $dob = strtotime($data['date_of_birth']);
        if ($dob > time()) {
            $errors['date_of_birth'] = 'Birth date cannot be in the future';
        }
    }
    
    if (!empty($data['weight']) && (!is_numeric($data['weight']) || $data['weight'] < 0)) {
        $errors['weight'] = 'Invalid weight';
    }
    
    return $errors;
}

// Validate appointment data
function validateAppointment($data) {
    $errors = [];
    
    if (empty($data['pet_id'])) {
        $errors['pet_id'] = 'Please select a pet';
    }
    
    if (empty($data['appointment_date'])) {
        $errors['appointment_date'] = 'Date is required';
    } else {
        $date = strtotime($data['appointment_date']);
        if ($date < strtotime('today')) {
            $errors['appointment_date'] = 'Cannot book appointments in the past';
        }
    }
    
    if (empty($data['appointment_time'])) {
        $errors['appointment_time'] = 'Time is required';
    }
    
    if (empty($data['reason'])) {
        $errors['reason'] = 'Please provide a reason for visit';
    }
    
    return $errors;
}

// Validate medical record data
function validateMedicalRecord($data) {
    $errors = [];
    
    if (empty($data['pet_id'])) {
        $errors['pet_id'] = 'Pet is required';
    }
    
    if (empty($data['visit_date'])) {
        $errors['visit_date'] = 'Visit date is required';
    }
    
    if (empty($data['diagnosis'])) {
        $errors['diagnosis'] = 'Diagnosis is required';
    }
    
    if (empty($data['treatment'])) {
        $errors['treatment'] = 'Treatment is required';
    }
    
    return $errors;
}

// Sanitize and validate input
function validateInput($input, $type = 'string', $required = true) {
    $input = trim($input);
    
    if ($required && empty($input)) {
        return false;
    }
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
        case 'phone':
            return preg_match('/^[0-9+\-\s()]+$/', $input);
        case 'date':
            return strtotime($input) !== false;
        default:
            return !empty($input) || !$required;
    }
}

