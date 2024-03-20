import {post} from "./ajax";

window.addEventListener('DOMContentLoaded', function () {

    const forgotPasswordBtn = document.querySelector('.forgot-password-btn');
    const resetPasswordBtn = document.querySelector('.reset-password-btn');


    if (forgotPasswordBtn){
        forgotPasswordBtn.addEventListener('click', function () {

            const forgotPasswordForm = document.querySelector('.forgot-password-form');
            const email = forgotPasswordForm.querySelector('input[name="email"]').value;

            post('/sendPasswordResetEmail', {email, ...loadingSpinner(forgotPasswordBtn)}, forgotPasswordForm)
                .then(response => {
                    if (response.ok) {
                        forgotPasswordBtn.innerHTML = "Email sent!";
                        forgotPasswordBtn.classList.remove('btn-primary');
                        forgotPasswordBtn.classList.add('btn-secondary');
                    }
                    else {
                        forgotPasswordBtn.innerHTML = 'Continue';
                        forgotPasswordBtn.removeAttribute('disabled');
                    }
                })
        })
    }

    if (resetPasswordBtn){
        resetPasswordBtn.addEventListener('click', function (event) {
            const resetPasswordForm = getFormData('.reset-password-form');
            post(resetPasswordForm.action, resetPasswordForm.inputs, resetPasswordForm.form)
                .then(response => {
                    if (response.ok){

                        window.location.replace('/login');
                    }
                })

        })
    }

})

function loadingSpinner(button)
{

    button.setAttribute('disabled', true)

    button.innerHTML = `
            <div class="spinner-grow spinner-grow-sm text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow spinner-grow-sm text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow spinner-grow-sm text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
        `;

    return {};
}

function getFormData(id)
{
    const form = document.querySelector(id);

    return {
        'form': form,
        'inputs': Object.fromEntries((new FormData(form)).entries()),
        'action': form.action
    }
}
