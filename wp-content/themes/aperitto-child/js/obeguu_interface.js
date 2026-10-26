document.addEventListener('DOMContentLoaded', () => {
  // const paging_year = document.querySelector('[name=obeguu-year]');
  const form = document.querySelector('#obeguu-form');
  const select = document.querySelectorAll('[name=obeguu-year],[name=obeguu-month]');
  select.forEach((select) => select.addEventListener('change', (event) => {
    const formData = new FormData(form);
    let monthQuery = '&obeguu-month=' + (formData.get('obeguu-month') ?? '1');

    window.location.href = window.location.pathname + '?page=oberin_logs_manage&obeguu-year=' + formData.get('obeguu-year') + monthQuery;
  }));

  let trace; // カーソル追従エレメント
  let moveCount = 0; // 歩きアニメーション
  let personClick; // パーソンクリック

  document.body.addEventListener('mousemove', (event) => {
    if (trace && !document.querySelector('#person-window')) {
      const img = trace.querySelector('img');
      trace.style.left = event.clientX - img.width / 2 + 'px';
      trace.style.top = event.clientY - img.height / 2 + 'px';
      personHover((Math.floor(moveCount) / 8) % 4 < 2);
      moveCount += 1;
    }
  });

  function personHover(front) {
    const [f, r] = document.querySelectorAll('#obeguu-person img');
    // ホバーしたら交換
    if (front) {
      f.className = 'hidden';
      r.className = 'visible';
    } else {
      f.className = 'visible';
      r.className = 'hidden';
    }
  }

  const person = document.querySelector('#obeguu-person');
  person.addEventListener('mouseenter', (event) => {
    personHover(true);
  });
  person.addEventListener('mouseleave', (event) => {
    personHover(false);
  });

  person.addEventListener('click', (event) => {
    const bag = document.querySelector('#bag');
    bag.className = 'visible';
    window.document.body.style.cursor = 'none';
    trace = event.currentTarget;
    trace.style.position = 'fixed';
    trace.style.pointerEvents = 'none';
    const table = document.querySelector('#loglist');
    if (table) {
      personClick = async (event) => {
        event.preventDefault(); // await より前に実行すること
        if (event.target.href) {
          event.target.dispatchEvent(new Event('personclick'));
          const response = await fetch(event.target.href);
          if (response.status === 404) {
            console.log("ページが見つかりません（404）");
            return;
          }
          const text = await response.text();
          if (text) {
            // PersonWindowポップアップ
            personPickup(text);
          }
        }
      };
      table.addEventListener('click', personClick);
    }
    return false;
  });

  const bag = document.querySelector('#bag');
  bag.addEventListener('click', (event) => {
    personHover(false);
    bag.className = 'hidden';
    window.document.body.style.cursor = '';
    if (trace) {
      trace.style.position = 'static';
      trace.style.pointerEvents = 'auto';
      trace = null;
    }
    if (personClick) {
      const table = document.querySelector('#loglist');
      if (table) {
        table.removeEventListener('click', personClick);
      }
    }
  });

  // PersonWindowポップアップ
  function personPickup(text) {
    matches = text.match(/^\((.+?)\):/mg);
    // console.log(matches);
    const persons = {};
    for (const name of matches) {
      persons[name] = (persons[name] ?? 0) + 1;
    }
    // const persons = [...new Set(matches)];
    // persons.sort((a, b) => a.localeCompare(b));
    const div = document.createElement('div');
    div.style =`
    background: rgba(192, 128, 0, 0.4);
    z-index: 9000;
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    cursor: '';
    `;

    document.body.append(div);
    document.body.className = 'no-scroll';

    const [flex, textwindow, header, hr, textarea] = [
      document.createElement('div'),
      document.createElement('div'),
      document.createElement('div'),
      document.createElement('hr'),
      document.createElement('pre'),
    ]
    ;
    flex.addEventListener('click', (event) => {
      if (event.target === event.currentTarget) {
        div.remove();
        document.body.className = '';
      }
    });
    flex.style =`
    background: transparent;
    z-index: 9001;
    display: flex;
    align-items: start;
    justify-content: center;
    width:100%;
    height: 100%;
    cursor: auto;
    `;
    textwindow.style =`
    display: flex;
    flex-direction: column;
    background: #debd83;
    width: 20rem;
    min-width: 25%;
    max-width: 90%;
    height: 80%;
    padding: 0.5rem;
    position: relative;
    top: 10%;
    `;
    textarea.style =`
    background: #debd83;
    flex: fit-content;
    overflow-y: scroll;
    text-wrap: auto;
    border: 0;
    text-shadow: none;
    padding: 0;
    margin: 0;
    font-size: 1rem;
    `;
    const img  = wpValues.imagePath + "person_shadow.png";
    header.style = `
    font-family: "Apple chancery", "Monotype Corsiva";
    font-size: 3rem;
    line-height: 2.5rem;
    display: flex;
    flex: none;
    align-items: end;
    height: 3rem;
    over-flow: hidden;
    background: url("${img}") no-repeat right top;
    cursor: pointer;
    `;
    hr.style = `
    border-width: 1px;
    width: 98%;
    margin: 0 0 0.5rem 0;
    `;
    hr.style.color = getComputedStyle(document.body).color;

    header.innerHTML = 'Persons';
    header.id = 'persons-header';

    let ancors = '';
    for (const [i, v] of Object.entries(persons)) {
      const p = i.replace(/^\((.+)\):$/, "$1");
      ancors += `<a href="" data-person="${p}">${p} (${v})</a><br />`;
    }

    textarea.innerHTML = ancors;
    textwindow.append(header, hr, textarea);
    textwindow.addEventListener('click', (event) => {
      event.preventDefault();
      if (event.target.href) {
        // Persons talk を開く
        const f = document.createElement('div');
        const preg = `^(${event.target.dataset.person}):.*`.replaceAll('(', '\\(').replaceAll(')', '\\)')
        const reg = new RegExp(preg, 'mg');
        const personsTalk = text.match(reg);
        textwindow.replaceChildren();
        header.innerHTML = '&lt;&lt; Persons';
        textarea.innerHTML = personsTalk.join('\n');
        textwindow.append(header, hr, textarea);
        textwindow.className = 'persons-talk';
      } else if (event.target.id == 'persons-header') {
        // Persons select に戻る
        if (textwindow.className == 'persons-talk') {
          textwindow.replaceChildren();
          textarea.innerHTML = ancors;
          header.innerHTML = 'Persons';
          textwindow.append(header, hr, textarea);
          // textarea.append(header, hr);
          // textarea.innerHTML += ancors;
          textwindow.className = 'persons-select';
        } else {
          div.remove();
          document.body.className = '';
        }
      }
    });
    flex.append(textwindow)
    div.append(flex);
    div.id = 'person-window';
    // console.log(persons);
  }

  // function ()
});
