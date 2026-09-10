document.addEventListener('DOMContentLoaded', function () {
  const app = document.querySelector('.hexis-app');
  if (!app || typeof HexisData === 'undefined') return;

  const angleMap = {
    '-3': 60,
    '-2': 40,
    '-1': 20,
    '0': 0,
    '1': -20,
    '2': -40,
    '3': -60
  };

  const saveTimers = new Map();

  function updateArrow(card) {
    const direction = card.dataset.direction;
    const arrow = card.querySelector('.hexis-arrow');
    const label = card.querySelector('.hexis-direction-label');

    if (direction === '') {
      arrow.style.transform = 'rotate(0deg)';
      arrow.classList.add('is-unset');
      label.textContent = 'Not assessed yet';
      return;
    }

    arrow.classList.remove('is-unset');
    arrow.style.transform = `rotate(${angleMap[direction]}deg)`;
    label.textContent = HexisData.labels[direction] || '';
  }

  function updateProgress() {
    const cards = Array.from(app.querySelectorAll('.hexis-virtue'));
    const rated = cards.filter(card => card.dataset.direction !== '').length;
    const count = app.querySelector('.hexis-rated-count');
    const bar = app.querySelector('.hexis-progress span');
    if (count) count.textContent = rated;
    if (bar) bar.style.width = `${(rated / cards.length) * 100}%`;
  }

  function setSaveState(text) {
    const el = app.querySelector('.hexis-save-state');
    if (el) el.textContent = text;
  }

  function saveCard(card, delay = 250) {
    const key = card.dataset.virtue;
    if (saveTimers.has(key)) clearTimeout(saveTimers.get(key));

    setSaveState('Saving…');

    const timer = setTimeout(async () => {
      const form = new URLSearchParams();
      form.append('action', 'hexis_save_assessment');
      form.append('nonce', HexisData.nonce);
      form.append('year', HexisData.year);
      form.append('virtue_key', key);
      form.append('direction', card.dataset.direction);
      form.append('is_focus', card.querySelector('.hexis-focus input').checked ? '1' : '0');
      form.append('note', card.querySelector('.hexis-note').value);

      try {
        const response = await fetch(HexisData.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString()
        });
        const json = await response.json();
        setSaveState(json.success ? 'Saved ✓' : 'Save failed');
      } catch (e) {
        setSaveState('Save failed');
      }
    }, delay);

    saveTimers.set(key, timer);
  }

  app.querySelectorAll('.hexis-virtue').forEach(card => {
    updateArrow(card);

    card.querySelectorAll('.hexis-angle-point').forEach(point => {
      point.addEventListener('click', () => {
        const value = point.dataset.value;
        card.dataset.direction = value;
        card.querySelectorAll('.hexis-angle-point').forEach(p => p.classList.toggle('is-selected', p === point));
        updateArrow(card);
        updateProgress();
        saveCard(card, 0);
      });
    });

    const focus = card.querySelector('.hexis-focus input');
    focus.addEventListener('change', () => {
      card.classList.toggle('is-focus', focus.checked);
      saveCard(card, 0);
    });

    const note = card.querySelector('.hexis-note');
    note.addEventListener('input', () => saveCard(card, 650));
    note.addEventListener('blur', () => saveCard(card, 0));

    card.querySelector('.hexis-note-toggle').addEventListener('click', e => {
      const wrap = card.querySelector('.hexis-note-wrap');
      wrap.hidden = !wrap.hidden;
      if (!wrap.hidden) note.focus();
      if (note.value.trim()) e.currentTarget.textContent = 'Reflection ✓';
    });

    card.querySelector('.hexis-definition-toggle').addEventListener('click', e => {
      const definition = card.querySelector('.hexis-definition');
      const expanded = e.currentTarget.getAttribute('aria-expanded') === 'true';
      e.currentTarget.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      definition.hidden = expanded;
    });
  });

  app.querySelectorAll('.hexis-group-toggle').forEach(button => {
    button.addEventListener('click', () => {
      const body = button.nextElementSibling;
      const open = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', open ? 'false' : 'true');
      button.lastElementChild.textContent = open ? '+' : '−';
      body.hidden = open;
    });
  });

  const complete = app.querySelector('.hexis-complete-review');
  if (complete) {
    complete.addEventListener('click', async () => {
      complete.disabled = true;
      complete.textContent = 'Completing…';
      const form = new URLSearchParams();
      form.append('action', 'hexis_complete_review');
      form.append('nonce', HexisData.nonce);
      form.append('year', HexisData.year);

      try {
        const response = await fetch(HexisData.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString()
        });
        const json = await response.json();
        complete.textContent = json.success ? 'Annual Review Complete ✓' : 'Try again';
      } catch (e) {
        complete.textContent = 'Try again';
      } finally {
        complete.disabled = false;
      }
    });
  }

  updateProgress();
});
