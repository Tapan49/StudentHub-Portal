document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const mobileInput = document.getElementById('mobile');
    const courseInput = document.getElementById('course');
    const yearInput = document.getElementById('year');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const termsInput = document.getElementById('terms');
    const strengthBar = document.getElementById('strengthBar');
    
    const errorSpans = {
        name: document.getElementById('nameError'),
        email: document.getElementById('emailError'),
        mobile: document.getElementById('mobileError'),
        course: document.getElementById('courseError'),
        year: document.getElementById('yearError'),
        gender: document.getElementById('genderError'),
        password: document.getElementById('passwordError'),
        confirmPassword: document.getElementById('confirmPasswordError'),
        terms: document.getElementById('termsError')
    };

    const regexPatterns = {
        name: /^[a-zA-Z\s]{3,}$/,
        email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
        mobile: /^\d{10}$/
    };

    function validateField(input, regex, errorElement, emptyMessage, invalidMessage) {
        if (input.value.trim() === '') {
            errorElement.textContent = emptyMessage;
            return false;
        } else if (regex && !regex.test(input.value.trim())) {
            errorElement.textContent = invalidMessage;
            return false;
        }
        errorElement.textContent = '';
        return true;
    }

    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength += 1;
        if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength += 1;
        if (/\d/.test(password)) strength += 1;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 1;

        strengthBar.className = '';
        if (password.length === 0) {
            strengthBar.className = '';
        } else if (strength <= 1) {
            strengthBar.className = 'strength-weak';
        } else if (strength === 2 || strength === 3) {
            strengthBar.className = 'strength-medium';
        } else if (strength >= 4) {
            strengthBar.className = 'strength-strong';
        }
    }

    function validatePassword() {
        checkPasswordStrength(passwordInput.value);
        if (passwordInput.value === '') {
            errorSpans.password.textContent = 'Password is required.';
            return false;
        } else if (passwordInput.value.length < 8) {
            errorSpans.password.textContent = 'Password must be at least 8 characters.';
            return false;
        }
        errorSpans.password.textContent = '';
        return true;
    }

    function validateConfirmPassword() {
        if (confirmPasswordInput.value === '') {
            errorSpans.confirmPassword.textContent = 'Confirm your password.';
            return false;
        } else if (confirmPasswordInput.value !== passwordInput.value) {
            errorSpans.confirmPassword.textContent = 'Passwords do not match.';
            return false;
        }
        errorSpans.confirmPassword.textContent = '';
        return true;
    }

    function validateGender() {
        const genderSelected = document.querySelector('input[name="gender"]:checked');
        if (!genderSelected) {
            errorSpans.gender.textContent = 'Please select a gender.';
            return false;
        }
        errorSpans.gender.textContent = '';
        return true;
    }

    function validateTerms() {
        if (!termsInput.checked) {
            errorSpans.terms.textContent = 'You must accept the terms and conditions.';
            return false;
        }
        errorSpans.terms.textContent = '';
        return true;
    }

    function validateSelect(input, errorElement, message) {
        if (input.value === '') {
            errorElement.textContent = message;
            return false;
        }
        errorElement.textContent = '';
        return true;
    }

    nameInput.addEventListener('input', () => validateField(nameInput, regexPatterns.name, errorSpans.name, 'Name is required.', 'Name must be at least 3 letters.'));
    emailInput.addEventListener('input', () => validateField(emailInput, regexPatterns.email, errorSpans.email, 'Email is required.', 'Enter a valid email address.'));
    mobileInput.addEventListener('input', () => validateField(mobileInput, regexPatterns.mobile, errorSpans.mobile, 'Mobile number is required.', 'Enter a valid 10-digit number.'));
    courseInput.addEventListener('change', () => validateSelect(courseInput, errorSpans.course, 'Please select a course.'));
    yearInput.addEventListener('change', () => validateSelect(yearInput, errorSpans.year, 'Please select a year.'));
    passwordInput.addEventListener('input', () => {
        validatePassword();
        if (confirmPasswordInput.value !== '') validateConfirmPassword();
    });
    confirmPasswordInput.addEventListener('input', validateConfirmPassword);
    document.querySelectorAll('input[name="gender"]').forEach(radio => radio.addEventListener('change', validateGender));
    termsInput.addEventListener('change', validateTerms);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const isNameValid = validateField(nameInput, regexPatterns.name, errorSpans.name, 'Name is required.', 'Name must be at least 3 letters.');
        const isEmailValid = validateField(emailInput, regexPatterns.email, errorSpans.email, 'Email is required.', 'Enter a valid email address.');
        const isMobileValid = validateField(mobileInput, regexPatterns.mobile, errorSpans.mobile, 'Mobile number is required.', 'Enter a valid 10-digit number.');
        const isCourseValid = validateSelect(courseInput, errorSpans.course, 'Please select a course.');
        const isYearValid = validateSelect(yearInput, errorSpans.year, 'Please select a year.');
        const isGenderValid = validateGender();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();
        const isTermsValid = validateTerms();

        if (isNameValid && isEmailValid && isMobileValid && isCourseValid && isYearValid && isGenderValid && isPasswordValid && isConfirmPasswordValid && isTermsValid) {
            form.submit();
        }
    });
});
