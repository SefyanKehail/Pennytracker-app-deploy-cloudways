import "../css/app.scss"
import {post} from "./ajax";

require('bootstrap')

window.addEventListener('DOMContentLoaded', function () {

    document.querySelector('.resend-activation-email-btn').addEventListener('click', function (event) {
        const resendActivationEmailBtn = event.currentTarget;

        //
        post(`/sendActivationEmail`,
            loadingSpinner(resendActivationEmailBtn),
            resendActivationEmailBtn.closest(".container")
        )
            .then(response => {
                if (response.status === 302) {
                    window.location.replace('/');
                } else {

                    if (response.ok) {
                        resendActivationEmailBtn.innerHTML = 'Email sent!';
                        resendActivationEmailBtn.classList.remove('btn-warning');
                        resendActivationEmailBtn.classList.add('btn-success');
                    } else {
                        resendActivationEmailBtn.innerHTML = 'Resend Activation Email';
                        resendActivationEmailBtn.removeAttribute('disabled');
                    }
                }
            })
    })
});


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