const ajax = (url, method = 'get', data = {}, domElement = null) => {
    method = method.toLowerCase()

    let options = {
        method, headers: {
            'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'
        }

    }
    const csrfMethods = new Set(['post', 'put', 'delete', 'patch'])

    if (csrfMethods.has(method)) {
        // we put csrf name value pairs + _METHOD trick
        const extraFields = {...getCsrfFields()};

        // handling for special methods
        if (method !== 'post') {

            extraFields._METHOD = options.method.toUpperCase()

            options.method = 'post'
        }

        // if it's an upload or uses FormData
        if (data instanceof FormData) {
            /*** @param FormData data***/

            delete options.headers["Content-Type"];

            for (const field in extraFields) {
                data.append(field, extraFields[field]);
            }

            options.body = data;

        } else {
            options.body = JSON.stringify({...data, ...extraFields})
        }
    } else {
        if (method === 'get' && !data) {
            url += '?' + (new URLSearchParams(data)).toString();
        }
    }


    return fetch(url, options).then(response => {

        if (domElement) {
            clearValidationErrors(domElement);
            clearRateLimitingError(domElement)
        }

        if (!response.ok) {
            if (response.status === 422) {
                response.json().then(errors => {
                    handleValidationErrors(domElement, errors);
                })
            }
            if (response.status === 429) {
                handleRateLimitingError(domElement);
            }
        }
        return response;
    })

}

const get = (url, data, domElement) => ajax(url, 'get', data, domElement)
const post = (url, data, domElement) => ajax(url, 'post', data, domElement)
const del = (url) => ajax(url, 'delete')

function getCsrfFields()
{
    const csrfNameField = document.querySelector('#csrfName')
    const csrfValueField = document.querySelector('#csrfValue')
    const csrfNameKey = csrfNameField.getAttribute('name')
    const csrfName = csrfNameField.content
    const csrfValueKey = csrfValueField.getAttribute('name')
    const csrfValue = csrfValueField.content

    return {
        [csrfNameKey]: csrfName, [csrfValueKey]: csrfValue
    }
}

function handleValidationErrors(domElement, errors)
{
    for (const fieldName in errors) {

        let field = domElement.querySelector(`[name=${fieldName}]`);

        const fieldOutline = field.closest('.form-outline');

        field.classList.add('is-invalid');

        const fieldInvalidFeedback = document.createElement('div');

        fieldInvalidFeedback.classList.add('invalid-feedback');

        fieldInvalidFeedback.textContent = (typeof errors[fieldName] === 'string' ? errors[fieldName] : errors[fieldName][0]);

        fieldOutline.appendChild(fieldInvalidFeedback);

    }
}

function handleRateLimitingError(domElement)
{
    const errorDiv = document.createElement("div");
    errorDiv.classList.add("alert","alert-danger","rate-limiting-alert");
    errorDiv.role = "alert";
    errorDiv.innerText = "Too many requests! Try again after sometime."

    domElement.prepend(errorDiv);
}

function clearRateLimitingError(domElement)
{
    const errorDiv = domElement.querySelector(".rate-limiting-alert");
    if (errorDiv){
        domElement.removeChild(errorDiv);
    }
}


function clearValidationErrors(domElement)
{
    domElement.querySelectorAll('.form-outline').forEach((element) => {
        const inputElement = element.querySelector('.is-invalid');

        const divElement = element.querySelector('.invalid-feedback');

        if (inputElement) {
            inputElement.classList.remove('is-invalid');
            element.removeChild(divElement);
        }
    })
}


export {
    ajax, get, post, del
}