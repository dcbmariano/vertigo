
const dropzones = document.querySelectorAll(".dropzone");

dropzones.forEach(zone => {

    const input =
        document.getElementById(
            zone.dataset.input
        );

    const button =
        zone.querySelector("button");

    const fileList =
        zone.querySelector(".file-list");

    button.addEventListener(
        "click",
        () => input.click()
    );

    input.addEventListener(
        "change",
        () => updateList(input, fileList)
    );

    zone.addEventListener("dragover", e => {
        e.preventDefault();
        zone.classList.add("dragover");
    });

    zone.addEventListener("dragleave", () => {
        zone.classList.remove("dragover");
    });

    zone.addEventListener("drop", e => {

        e.preventDefault();

        zone.classList.remove("dragover");

        input.files = e.dataTransfer.files;

        updateList(input, fileList);

    });

});

function updateList(input, container) {

    container.innerHTML = "";

    const files = [...input.files];

    if (files.length === 0) {

        container.innerHTML =
            "<em>No files selected</em>";

        return;
    }

    const maxVisible = 5;

    files
        .slice(0, maxVisible)
        .forEach(file => {

            const item =
                document.createElement("div");

            item.textContent = file.name;

            container.appendChild(item);

        });

    if (files.length > maxVisible) {

        const more =
            document.createElement("div");

        more.style.fontWeight = "600";

        more.style.marginTop = "8px";

        more.textContent =
            `+ ${files.length - maxVisible} additional files`;

        container.appendChild(more);
    }

    const total =
        document.createElement("div");

    total.style.marginTop = "10px";
    total.style.fontWeight = "700";

    total.textContent =
        `Total: ${files.length} file(s)`;

    container.appendChild(total);

}

const clearButtons =
    document.querySelectorAll(".clear-btn");

clearButtons.forEach(btn => {

    btn.addEventListener("click", function () {

        const zone =
            btn.closest(".dropzone");

        const input =
            document.getElementById(
                zone.dataset.input
            );

        const fileList =
            zone.querySelector(".file-list");

        input.value = "";

        fileList.innerHTML =
            "<em>No files selected</em>";

    });

});

document.getElementById('uploadForm')
.addEventListener('submit', function(e){

    const protein =
        document.getElementById('proteinInput').files.length;

    const rna =
        document.getElementById('rnaInput').files.length;

    const structure =
        document.getElementById('structureInput').files.length;

    if(!protein || !structure){

        e.preventDefault();

        alert(
            'Protein FASTA and a structure file (PDB/CIF) are required.'
        );

    }

});
