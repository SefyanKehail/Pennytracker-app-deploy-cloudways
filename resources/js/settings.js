import "../css/settings.scss"
import {post, get} from "./ajax";


window.addEventListener('DOMContentLoaded', function () {

    const profileContent = document.querySelector('#profileContent')

    if (profileContent) {
        //  ----------- Profile Settings ------------
        profileContent.addEventListener('click', function (event) {

            //  ----------- Update Name ------------
            const saveChangesBtn = event.target.closest('.save-changes-btn');

            const profileForm = getFormData('.profile-update-form');

            if (saveChangesBtn) {
                post(profileForm.action, profileForm.inputs, profileForm.form)
                    .then(response => {
                        if (response.ok) {
                            window.location.replace('/settings/profile');
                        }
                    })
            }


            //  ----------- Update Password ------------
            const changePasswordBtn = event.target.closest('.change-password-btn');

            const passwordForm = getFormData('.change-password-form');

            if (changePasswordBtn) {
                post(passwordForm.action, passwordForm.inputs, passwordForm.form)
                    .then(response => {
                        if (response.ok) {
                            window.location.replace('/login')
                        }
                    })
            }
        })
    }


    //  ----------- Authentication Settings ------------

    const authenticationContent = document.querySelector('#authenticationContent')


    if (authenticationContent) {

        window.addEventListener('load', function () {
            const display = document.querySelector('.countdown');

            if (display) {
                let duration = display.getAttribute('data-timestamp');
                startTimer(duration, display, function () {
                    const endDisplay = document.querySelector('.active-2FA-code');
                    endDisplay.innerHTML = `<p class="mb-0">No currently active code.</p>`;
                });
            }

        })
        authenticationContent.addEventListener('click', function (event) {
            const toggle2FABtn = event.target.closest('.toggle-2FA-btn');
            const disableActiveCodeBtn = event.target.closest('.disable-active-code-btn');

            const toggle2FAForm = getFormData('.toggle-2FA-form');

            //  ----------- toggle 2FA ------------

            if (toggle2FABtn) {
                post(toggle2FAForm.action, toggle2FAForm.inputs, toggle2FAForm.form)
                    .then(response => {
                        if (response.ok) {
                            window.location.replace('/login')
                        }
                    })
            }

            //  ----------- disable active code  ------------

            if (disableActiveCodeBtn) {
                if (confirm('Are you sure you want to disable the active code?')) {
                    post('/settings/authentication/disableCode')
                        .then(response => {
                            if (response.ok) {
                                window.location.replace('/settings/authentication')
                            }
                        })
                }
            }


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

function startTimer(duration, display, endAction)
{
    var timer = duration, minutes, seconds;
    setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            timer = duration;
        }

        if (timer === 0) {
            endAction();
        }
    }, 1000);
}
