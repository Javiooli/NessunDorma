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