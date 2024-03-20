const getGranularity = (dateRange) => {
    if (['TODAY', 'hourly'].includes(dateRange)) {
        return 'Hourly Summary';
    }

    if (dateRange === 'weekly') {
        return 'Weekly Summary'
    }

    if (['WTD', 'daily', 'MTD'].includes(dateRange)) {
        return 'Daily Summary';
    }

    if (dateRange === 'yearly') {

        return 'Yearly Summary'
    }

    if (['YTD', 'monthly', ''].includes(dateRange)) {
        return 'Monthly Summary';
    }
}

export {
    getGranularity
}