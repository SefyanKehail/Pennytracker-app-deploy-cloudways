import "../css/dashboard.scss"
import Chart from 'chart.js/auto'
import {get, post} from './ajax'
import {flash, getFlash, removeFlash} from "./session";
import {getGranularity} from "./chart_utils"

window.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('yearToDateChart')
    const dashboard = document.querySelector('.dashboard');
    const dateRange = dashboard.getAttribute('data-date-range');
    const applyCustomDateBtn = document.querySelector('.apply-custom-date-btn');
    const chartSpinner = document.querySelector('.chart-spinner');


    if (applyCustomDateBtn) {

        applyCustomDateBtn.addEventListener('click', function () {
            const customDateForm = getFormData('.custom-date-form');

            const startDate = customDateForm.inputs.startDate;
            const endDate = customDateForm.inputs.endDate;
            const granularity = customDateForm.inputs.granularity;

            flash('startDate', startDate)
            flash('endDate', endDate)
            flash('granularity', granularity)

            post(customDateForm.action, customDateForm.inputs, customDateForm.form)
                .then(response => {

                    if (response.ok) {
                        window.location.replace(`${response.url}?startDate=${startDate}&endDate=${endDate}&granularity=${granularity}`);
                    }
                })
        })
    }

    const startDate = getFlash('startDate');
    const endDate = getFlash('endDate');
    const granularity = getFlash('granularity');


    // in case we're using custom date range
    let baseUrl = startDate && endDate && granularity
        ? `/stats/${dateRange}?startDate=${startDate}&endDate=${endDate}&granularity=${granularity}`
        : `/stats/${dateRange}`

    if (dateRange !== 'customDate') {
        baseUrl = `/stats/${dateRange}`;
        removeFlash('startDate');
        removeFlash('endDate');
        removeFlash('granularity')
    }

    get(baseUrl)
        .then(response => response.json()).then(response => {

        let labels = response.map(row => {
            if (dateRange === 'TODAY' || granularity === 'hourly') {
                return row.label += "h";
            }
            return row.label;
        });

        let expensesData = {};
        let incomeData = {};
        const title = granularity === null ? getGranularity(dateRange) : getGranularity(granularity);

        response.forEach(({label, expense, income}) => {
            expensesData[label] = expense;
            incomeData[label] = income;
        })

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Expense',
                        data: expensesData,
                        borderWidth: 2,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                    },
                    {
                        label: 'Income',
                        data: incomeData,
                        borderWidth: 2,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                    }
                ]
            },
            options: {
                scales: {
                    // x: {
                    //
                    // },
                    y: {
                        beginAtZero: true
                    },
                },
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        position: "bottom"
                    }
                },
                spanGaps: true,
            }
        })
    })

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


