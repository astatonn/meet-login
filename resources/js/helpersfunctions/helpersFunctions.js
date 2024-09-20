export const togglePass = (e) => {
    const inputToggle = document.getElementById("password");
    const togglePassword = document.getElementById("togglePass");

    if (inputToggle.type == "password") {
        inputToggle.setAttribute('type', 'text');
        togglePassword.classList.remove('button-hide-pass')
        togglePassword.classList.add('button-show-pass')
    }

    else {
        inputToggle.setAttribute('type', 'password');
        togglePassword.classList.remove('button-show-pass')
        togglePassword.classList.add('button-hide-pass')
    }
} 

export const captureData = (e) => {
    let captureEmail = document.getElementById('email').value
    let capturePassword = document.getElementById('password').value
    let obj = {}
    obj['email'] = captureEmail;
    obj['password'] = capturePassword;
    return obj
}