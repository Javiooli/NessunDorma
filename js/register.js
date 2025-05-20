const passwordInput = document.getElementById('password');
const strengthBar = document.getElementById('strength-bar');
const strengthText = document.getElementById('strength-text');

passwordInput.addEventListener('input', () => {
    const password = passwordInput.value;
    let strength = 0;

    // Criterios de evaluación
    if (password.length > 0) strength += 10;
    if (password.length >= 8) strength += 20;
    if (password.length >= 12) strength += 10;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[a-z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 20;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;

    // Limitar strength a 100
    strength = Math.min(strength, 100);

    // Actualizar barra
    strengthBar.style.width = strength + '%';

    // Cambiar color y texto según la fuerza
    if (strength <= 33) {
        strengthBar.className = 'weak';
        strengthText.textContent = 'Débil';
        strengthText.classList.add('visible');
    } else if (strength <= 66) {
        strengthBar.className = 'medium';
        strengthText.textContent = 'Media';
        strengthText.classList.add('visible');
    } else {
        strengthBar.className = 'strong';
        strengthText.textContent = 'Fuerte';
        strengthText.classList.add('visible');
    }

    // Limpiar si no hay contraseña
    if (password.length === 0) {
        strengthBar.style.width = '0%';
        strengthText.classList.remove('visible');
    }
});

const EyeBtn = document.getElementById('eye');
const EyeBtn2 = document.getElementById('eye-closed');
const passwordField = document.getElementById('password');

EyeBtn.addEventListener('click', () => {
    passwordField.type = 'password';
    EyeBtn2.style.display = 'block';
    EyeBtn.style.display = 'none';
});

EyeBtn2.addEventListener('click', () => {
    passwordField.type = 'text';
    EyeBtn.style.display = 'block';
    EyeBtn2.style.display = 'none';
});

const ConfirmEyeBtn = document.getElementById('eye-confirm');
const ConfirmEyeBtn2 = document.getElementById('eye-confirm-closed');
const confirmPasswordField = document.getElementById('confirm-password');
ConfirmEyeBtn.addEventListener('click', () => {
    confirmPasswordField.type = 'password';
    ConfirmEyeBtn2.style.display = 'block';
    ConfirmEyeBtn.style.display = 'none';
});
ConfirmEyeBtn2.addEventListener('click', () => {
    confirmPasswordField.type = 'text';
    ConfirmEyeBtn.style.display = 'block';
    ConfirmEyeBtn2.style.display = 'none';
});