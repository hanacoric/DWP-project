function enableEdit(fieldID) {
    const element = document.getElementById(fieldID);
    const currentValue = element.textContent;

    const input = document.createElement("input");
    input.type = "text";
    input.id = `${fieldID}-input`;
    input.value = currentValue;
    input.className = "edit-input";

    element.parentNode.replaceChild(input, element);

    input.addEventListener("blur", () => saveChanges(fieldID, input.value));
    input.addEventListener("keypress", (event) => {
        if (event.key === "Enter") {
            saveChanges(fieldID, input.value);
        }
    });

    input.focus();
}

function saveChanges(fieldID, newValue) {
    const input = document.getElementById(`${fieldID}-input`);
    if (input) {
        const displayElement = document.createElement("span");
        displayElement.id = fieldID;
        displayElement.className = "editable";
        displayElement.textContent = newValue;

        document.getElementById("hidden-field-" + fieldID).value = newValue;
        input.parentNode.replaceChild(displayElement, input);
    }
}
console.log("profile.js loaded");
