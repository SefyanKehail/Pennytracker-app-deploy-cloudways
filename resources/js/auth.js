import "../css/auth.scss";
import {Modal} from "bootstrap";
import {post} from "./ajax";


window.addEventListener('DOMContentLoaded', function () {


    const twoFactorModalDOM = document.getElementById('twoFactorModal');

    if (twoFactorModalDOM){
        var twoFactorModal = new Modal(twoFactorModalDOM);
    }


    // login (regular) -> post request AuthController -> login
    const loginBtn = document.querySelector('.login-btn');

    if (loginBtn) {
        loginBtn.addEventListener('click', function (event) {

            const loginForm = getFormData('.login-form');

            post(loginForm.action, loginForm.inputs, loginForm.form)
                .then(response => response.json())
                .then(response => {
                    if (response.two_factor) {
                        twoFactorModal.show()
                    } else {
                        window.location.replace('/');
                    }

                })
        })
    }


    // login with 2FA
    const login2FABtn = document.querySelector('.login-2FA-btn');

    if (login2FABtn) {
        login2FABtn.addEventListener('click', function (event) {
            const code = document.querySelector('input[name="code"]').value;
            const email = document.querySelector('.login-form input[name="email"]').value;

            post('/loginWith2FA', {email, code}, twoFactorModal._element)
                .then(response => {
                    if (response.ok) {
                        window.location.replace('/');
                    }
                })
        })
    }


    // Register
    const registerBtn = document.querySelector(".register-btn");

    if (registerBtn) {
        registerBtn.addEventListener('click', function (event) {

            const registerForm = getFormData('.register-form');

            post(registerForm.action, registerForm.inputs, registerForm.form)
                .then(response => {
                    if (response.ok) {
                        window.location.replace('/');
                    }
                })
        })
    }

})


function getFormData(id)
{
    const form = document.querySelector(id);

    return {
        'form': form,
        'inputs': Object.fromEntries((new FormData(form)).entries()),
        'action': form.action
    }
}