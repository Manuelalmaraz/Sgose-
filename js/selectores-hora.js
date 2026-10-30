// Sustituye las listas nativas por listas compactas; conserva el select para enviar el formulario.
document.querySelectorAll('[data-selector-compacto]').forEach((select) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'selector-compacto';
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.id = select.id + '-boton';
    trigger.className = 'selector-hora-boton';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    const list = document.createElement('div');
    list.id = select.id + '-lista';
    list.className = 'selector-hora-lista';
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    trigger.setAttribute('aria-controls', list.id);
    const label = document.querySelector(`label[for="${select.id}"]`);
    if (label) {
        label.htmlFor = trigger.id;
        label.id = select.id + '-etiqueta';
        list.setAttribute('aria-labelledby', label.id);
    }
    const close = () => {
        list.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };
    const refresh = () => {
        trigger.textContent = select.options[select.selectedIndex].text + ' ▾';
        [...list.children].forEach((option, index) => {
            option.setAttribute('aria-selected', String(index === select.selectedIndex));
        });
    };
    [...select.options].forEach((option, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('role', 'option');
        button.textContent = option.text;
        button.addEventListener('click', () => {
            select.selectedIndex = index;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            refresh();
            close();
            trigger.focus();
        });
        list.append(button);
    });
    const open = () => {
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        const chosen = list.children[select.selectedIndex];
        list.scrollTop = Math.max(0, chosen.offsetTop - list.clientHeight / 2);
        chosen.focus({ preventScroll: true });
    };
    trigger.addEventListener('click', () => list.hidden ? open() : close());
    // Flechas, Inicio, Fin y Escape permiten elegir también sin usar el ratón.
    wrapper.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { close(); trigger.focus(); }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (list.hidden) { open(); return; }
            const index = [...list.children].indexOf(document.activeElement);
            list.children[(index + (event.key === 'ArrowDown' ? 1 : -1) + list.children.length) % list.children.length].focus();
        }
        if (!list.hidden && ['Home', 'End'].includes(event.key)) {
            event.preventDefault();
            list.children[event.key === 'Home' ? 0 : list.children.length - 1].focus();
        }
    });
    document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) close(); });
    wrapper.addEventListener('focusout', (event) => { if (!wrapper.contains(event.relatedTarget)) close(); });
    select.before(wrapper);
    wrapper.append(trigger, list);
    select.hidden = true;
    refresh();
});
