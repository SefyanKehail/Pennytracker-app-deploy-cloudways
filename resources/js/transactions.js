import {del, get, post} from "./ajax";
import {Modal} from "bootstrap";
import DataTable from "datatables.net";


window.addEventListener('DOMContentLoaded', function () {
    // loading table
    const newTransactionModal = new Modal(document.getElementById('newTransactionModal'));
    const editTransactionModal = new Modal(document.getElementById('editTransactionModal'));
    const uploadReceiptModal = new Modal(document.getElementById('uploadReceiptModal'));
    const uploadTransactionModal = new Modal(document.getElementById('uploadTransactionModal'))

    const table = new DataTable('#transactionsTable', {
        serverSide: true,
        ajax: '/transactions/load',
        orderMulti: false,
        processing: true,
        rowCallback: function (row, data) {
            if (!data.reviewed) {
                row.classList.add('fw-bold');
                row.style.backgroundColor = '#DADADA';
            }
            return row;
        },
        columns: [
            {data: 'description'},
            {
                data: 'amount',
                render: (data, type, row) => {
                    if (type === 'display' || type === 'filter') {
                        const amount = new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD',
                            currencySign: 'standard'
                        }).format(data);

                        return `<span class="${data > 0 ? 'text-success' : 'text-danger'}">${amount}</span>`

                    }
                    return data; // For sorting and other purposes, return the original data
                }
            },
            {data: 'category'},
            {
                sortable: false,
                data: 'receipts',
                render: (data, type, row) => {
                    if (type === 'display' || type === 'filter') {
                        let icons = []

                        for (let i = 0; i < data.length; i++) {
                            const receipt = data[i]

                            const span = document.createElement('span')
                            const anchor = document.createElement('a')
                            const icon = document.createElement('i')
                            const deleteIcon = document.createElement('i')

                            deleteIcon.role = 'button'

                            span.classList.add('position-relative')
                            icon.classList.add('bi', 'bi-file-earmark-text', 'download-receipt', 'text-primary', 'fs-4')
                            deleteIcon.classList.add('bi',
                                'bi-x-circle-fill',
                                'delete-receipt-btn',
                                'text-danger',
                                'position-absolute',
                                'end-0',
                                'z-1'
                            )
                            deleteIcon.style.fontSize = '65%';

                            anchor.href = `/transactions/${row.id}/receipts/${receipt.id}`
                            anchor.target = 'blank'
                            anchor.title = receipt.filename

                            deleteIcon.setAttribute('data-id', receipt.id)
                            deleteIcon.setAttribute('data-transactionId', row.id)

                            anchor.append(icon)
                            span.append(anchor)
                            span.append(deleteIcon)

                            icons.push(span.outerHTML)
                        }

                        return icons.join('')

                    }
                    return data; // For sorting and other purposes, return the original data
                }
            },
            {data: 'date'},
            {
                sortable: false,
                data: row => `
                    <div class="d-flex gap-2">

                        <div class="dropdown">
                          
                          <i class="bi bi-gear-fill fs-4" title="Actions" role="button" data-bs-toggle="dropdown"></i>
    
                          <div class="dropdown-menu" aria-labelledby="dropdownMenuTableButtons">
                                <button class="dropdown-item btn btn-outline-primary open-upload-receipt-btn"
                                    data-id="${row.id}">
                                <i class="bi bi-upload"></i>
                                &nbsp;&nbsp;Upload receipt
                                </button>                       
    
                                <button class="dropdown-item btn btn-outline-primary edit-transaction-btn"
                                    data-id="${row.id}">
                                <i class="bi bi-pencil-fill"></i>
                                &nbsp;&nbsp;Edit
                                </button>
    
                                <button class="dropdown-item btn btn-outline-primary delete-transaction-btn"
                                    data-id="${row.id}">
                                <i class="bi bi-trash3-fill"></i>
                                &nbsp;&nbsp;Delete
                                </button>     
                                
                                <button class="dropdown-item btn btn-outline-primary 
                                        toggle-reviewed-btn bi ${row.reviewed ? 'bi-check-square' : 'bi-square'}"
                                    data-id="${row.id}">
                                &nbsp;&nbsp;Mark as reviewed
                                </button>                        
                          </div>
                        </div>    
                    </div>         
                `
            }
        ]
    });


    // *** Delete Edit Upload buttons ***
    document.querySelector('#transactionsTable').addEventListener('click', function (event) {
        const editBtn = event.target.closest('.edit-transaction-btn');
        const deleteBtn = event.target.closest('.delete-transaction-btn');
        const uploadBtn = event.target.closest('.open-upload-receipt-btn');
        const deleteReceiptBtn = event.target.closest('.delete-receipt-btn');
        const toggleReviewedBtn = event.target.closest('.toggle-reviewed-btn');

        if (editBtn) {
            const transactionId = editBtn.getAttribute('data-id')

            get(`/transactions/${transactionId}`)
                .then(response => response.json())
                .then(response => openEditTransactionModal(editTransactionModal, response))

            return;
        }

        if (deleteBtn) {
            const transactionId = deleteBtn.getAttribute('data-id')

            if (confirm('Are you sure you want to delete this transaction?')) {
                del(`/transactions/${transactionId}`)
                    .then((response) => {
                        if (response.ok) {
                            table.draw(false);
                        }
                    })
            }

            return;
        }

        if (uploadBtn) {
            const transactionId = uploadBtn.getAttribute('data-id');

            // set data-id in modal button for upload url
            const modalUploadBtn = uploadReceiptModal._element.querySelector('.upload-receipt-btn');
            modalUploadBtn.setAttribute('data-id', `${transactionId}`);

            uploadReceiptModal.show();

            return;
        }

        if (deleteReceiptBtn) {
            const receiptId = deleteReceiptBtn.getAttribute('data-id');
            const transactionId = deleteReceiptBtn.getAttribute('data-transactionId');

            if (confirm('Are you sure you want to delete this receipt?')) {
                del(`/transactions/${transactionId}/receipts/${receiptId}`)
                    .then(response => {
                        if (response.ok) {
                            table.draw(false);
                        }
                    })
            }

            return;
        }

        if (toggleReviewedBtn) {
            const transactionId = toggleReviewedBtn.getAttribute('data-id');

            post(`/transactions/${transactionId}/reviewed`)
                .then(response => {
                    if (response.ok) {
                        table.draw(false);
                    }
                })

        }

    })

// *** Creating a new transaction ***
    document.querySelector('.create-transaction-btn').addEventListener('click', function () {

        post('/transactions', {
            ...getTransactionFormData(newTransactionModal)
        }, newTransactionModal._element)
            .then(response => {
                if (response.ok) {
                    clearModal(newTransactionModal)

                    table.draw(false);
                    newTransactionModal.hide();
                }
            })
    })

// *** saving an edited transaction ***
    document.querySelector('.save-transaction-btn').addEventListener('click', function (event) {

        const transactionId = event.currentTarget.getAttribute('data-id')

        post(`/transactions/${transactionId}`,
            {...getTransactionFormData(editTransactionModal)}, editTransactionModal._element
        )
            .then(response => {
                if (response.ok) {
                    table.draw(false);
                    editTransactionModal.hide();
                }
            })
    })

// *** Uploading a new receipt ***
    document.querySelector('.upload-receipt-btn').addEventListener('click', function (event) {

        const transactionId = event.currentTarget.getAttribute('data-id');
        const button = event.target;
        const btnHtml = button.innerHTML;

        post(
            `/transactions/${transactionId}/receipts`,
            getFormData(uploadReceiptModal, 'receipt', button),
            uploadReceiptModal._element
        )
            .then(response => {
                button.removeAttribute('disabled');
                button.innerHTML = btnHtml;

                if (response.ok) {
                    table.draw(false);
                    uploadReceiptModal.hide();
                }
            })

            .finally(() => {
                clearModal(uploadReceiptModal);
            })
    })
// *** Uploading Transactions from CSV ***

    document.querySelector('.upload-transaction-btn').addEventListener('click', function (event) {

        const button = event.target;
        const btnHtml = button.innerHTML;

        post(
            '/transactions/upload',
            getFormData(uploadTransactionModal, 'transaction', button),
            uploadTransactionModal._element
        )
            .then(response => {
                if (response.ok) {
                    table.draw(false);
                    uploadTransactionModal.hide();
                }

                button.removeAttribute('disabled');
                button.innerHTML = btnHtml;

                const errorDiv = uploadTransactionModal._element.querySelector(".upload-alert");
                if (errorDiv){
                    uploadTransactionModal._element.querySelector('.modal-body').removeChild(errorDiv);
                }
            })

            .finally(() => {
                clearModal(uploadTransactionModal);
            })
    })

})


function openEditTransactionModal(modal, {id, description, date, amount, category})
{
    modal._element.querySelector('.save-transaction-btn').setAttribute('data-id', id);

    const dateObjectJS = new Date(date.date);

    const year = new Intl.DateTimeFormat('en', {year: 'numeric'}).format(dateObjectJS);
    const month = new Intl.DateTimeFormat('en', {month: '2-digit'}).format(dateObjectJS);
    const day = new Intl.DateTimeFormat('en', {day: '2-digit'}).format(dateObjectJS);
    const hour = new Intl.DateTimeFormat('en', {hour: '2-digit', hour12: false}).format(dateObjectJS);
    const minute = new Intl.DateTimeFormat('en', {minute: 'numeric'}).format(dateObjectJS);

    const inputMappings = {
        'description': description,
        'date': `${year}-${month}-${day}T${hour}:${minute}`,
        'amount': amount,
        'category': category // category here is the category id which can be used to select an option since options in the select field have value attribute as category id
    }

    for (let mappingKey in inputMappings) {
        let field = modal._element.querySelector(`input[name=${mappingKey}]`);

        if (!field) {
            field = modal._element.querySelector(`select[name=${mappingKey}]`);
            const option = field.querySelector(`option[value='${inputMappings[mappingKey]}']`);
            option.selected = true;
        }

        if (field.tagName.toLowerCase() !== 'select') {
            field.value = inputMappings[mappingKey];
        }
    }

    modal.show()
}


function getTransactionFormData(modal)
{
    let data = {};

    const fields = [
        ...modal._element.getElementsByTagName('input'),
        ...modal._element.getElementsByTagName('select')
    ]

    fields.forEach(field => {
        data[field.name] = field.value;
    })

    return data;
}


function clearModal(modal)
{
    const fields = [
        ...modal._element.getElementsByTagName('input'),
        ...modal._element.getElementsByTagName('select')
    ]

    fields.forEach(field => {
        field.value = "";
    })
}


function getFormData(modal, inputName, button)
{
    let formData = new FormData();

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
        `
    const files = modal._element.querySelector("input[type='file']").files;

    for (let i = 0; i < files.length; i++) {
        formData.append(inputName, files[i]);
    }

    const errorDiv = document.createElement("div");
    errorDiv.classList.add("alert","alert-primary","upload-alert");
    errorDiv.role = "alert";
    errorDiv.innerText = "This might take a while, please wait..."

    modal._element.querySelector('.modal-body').prepend(errorDiv);

    return formData;
}
