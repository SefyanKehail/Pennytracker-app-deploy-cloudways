const flash = (key, messages) => {
    sessionStorage.setItem(key, messages);
}

const getFlash = (key) => {
    const flashMessage = sessionStorage.getItem(key);

    if (flashMessage) {

        return flashMessage;
    }
    return null;
}

const removeFlash = (key) => {
    if ( sessionStorage.getItem(key)) {
        sessionStorage.removeItem(key);
    }
}

export {flash, getFlash, removeFlash}