import {Modal} from "bootstrap"
import {get, post, del} from "./ajax"
import DataTable from "datatables.net"

window.addEventListener('DOMContentLoaded', function () {
    const editCategoryModal = new Modal(document.getElementById('editCategoryModal'))

    const table = new DataTable('#categoriesTable', {
        serverSide: true,
        ajax: '/categories/load',
        orderMulti: false,
        processing: true,
        columns: [
            {data: 'name'},
            {data: 'createdAt'},
            {data: 'updatedAt'},
            {
                sortable: false,
                data: row => `
                        
                    <div class="d-flex flex-">
                        <div class="dropdown">
                          
                          <i class="bi bi-gear-fill fs-4" title="Actions" role="button" data-bs-toggle="dropdown"></i>
    
                          <div class="dropdown-menu" aria-labelledby="dropdownMenuTableButtons">                  
    
                                <button class="dropdown-item btn btn-outline-primary edit-category-btn"
                                    data-id="${row.id}">
                                <i class="bi bi-pencil-fill"></i>
                                &nbsp;&nbsp;Edit
                                </button>
    
                                <button class="dropdown-item btn btn-outline-primary delete-category-btn"
                                    data-id="${row.id}">
                                <i class="bi bi-trash3-fill"></i>
                                &nbsp;&nbsp;Delete
                                </button>     
                                                    
                          </div>
                        </div>  
                    </div>                
                `


            }
        ]
    });


    document.querySelector('#categoriesTable').addEventListener('click', function (event) {
        const editBtn = event.target.closest('.edit-category-btn');
        const deleteBtn = event.target.closest('.delete-category-btn');

        if (editBtn) {
            const categoryId = editBtn.getAttribute('data-id')

            get(`/categories/${categoryId}`)
                .then(response => response.json())
                .then(response => openEditCategoryModal(editCategoryModal, response))

        } else if (deleteBtn) {
            const categoryId = deleteBtn.getAttribute('data-id')

            if(confirm('Are you sure you want to delete this category?')) {
                del(`/categories/${categoryId}`)
                    .then(response => {
                        if (response.ok) {
                            table.draw(false)
                        }
                    })
                    .catch(error => {
                        console.error('Delete request failed', error);
                    });
            }
        }

    })

    // Creating a new category
    const newCategoryModal = document.querySelector('#newCategoryModal');
    const createButton = newCategoryModal.querySelector('.create-category-button');

    createButton.addEventListener('click', function () {

        post('/categories',
            {
                name: newCategoryModal.querySelector('input[name="name"]').value
            },
            newCategoryModal
        ).then(response => {
            if (response.ok) {
                newCategoryModal.querySelectorAll('input').forEach((element) => {
                    element.value = '';
                })
                table.draw(false);
                Modal.getInstance(newCategoryModal).hide();
            }
        })
    })


    document.querySelector('.save-category-btn').addEventListener('click', function (event) {
        const categoryId = event.currentTarget.getAttribute('data-id')

        post(`/categories/${categoryId}`, {
            name: editCategoryModal._element.querySelector('input[name="name"]').value
        }, editCategoryModal._element).then(response => {
            if (response.ok) {
                table.draw(false);
                editCategoryModal.hide();
            }
        })
    })

})

function openEditCategoryModal(modal, {id, name}) {
    const nameInput = modal._element.querySelector('input[name="name"]')

    nameInput.value = name

    modal._element.querySelector('.save-category-btn').setAttribute('data-id', id)

    modal.show()
}