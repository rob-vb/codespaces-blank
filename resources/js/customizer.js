document.addEventListener('change', (e) => {
    if (!e.target.closest('.js-customizer-tabs input')) return;

    const id = e.target.closest('.js-customizer-tabs input').getAttribute('data-id');
    console.log(id);

    document.querySelectorAll('.js-customizer-tab').forEach(tab => {
        tab.classList.add('hidden');
    });

    document.getElementById(id).classList.remove('hidden');
})
