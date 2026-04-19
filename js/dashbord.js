
const statuses = ["In Progress", "Complete", "Pending", "Approved", "Rejected"];
const classes = ["progress", "complete", "pending", "approved", "rejected"];

document.querySelectorAll(".status").forEach(el => {
    el.addEventListener("click", () => {
        let current = statuses.indexOf(el.innerText);
        let next = (current + 1) % statuses.length;

        el.innerText = statuses[next];
        el.className = "status " + classes[next];
    });
});

document.querySelectorAll("[contenteditable]").forEach(el => {
    el.addEventListener("focus", () => {
        el.style.background = "#fff3cd";
    });

    el.addEventListener("blur", () => {
        el.style.background = "transparent";
    });
});