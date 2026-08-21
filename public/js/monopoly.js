(() => {
    'use strict';

    const root = document.getElementById('four-player-game');
    const board = document.getElementById('monopoly-board');
    const playersElement = document.getElementById('monopoly-players');
    const message = document.getElementById('monopoly-message');
    if (!root || !board || !playersElement || !message) return;

    const tokenColors = ['#dc2626', '#2563eb', '#7c3aed', '#ea580c'];
    const groupColors = {brown:'#92400e',lightblue:'#38bdf8',pink:'#ec4899',orange:'#f97316',red:'#ef4444',yellow:'#eab308',green:'#16a34a',darkblue:'#1d4ed8',rail:'#111827',utility:'#64748b'};
    const propertySelect = document.getElementById('monopoly-property');
    const tradePlayerSelect = document.getElementById('monopoly-trade-player');
    const tradeStatus = document.getElementById('monopoly-trade-status');
    const tradeResponse = document.getElementById('monopoly-trade-response');
    const tradeOfferButton = document.getElementById('monopoly-trade-offer');
    let busy = false;
    let lastCardKey = '';

    const money = value => `₺${Number(value || 0).toLocaleString('tr-TR')}`;
    const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
    const place = index => index <= 10 ? [11, 11-index] : index <= 20 ? [21-index, 1] : index <= 30 ? [1, index-19] : [index-29, 11];

    function currentSeat(room) {
        return room.players.find(player => Number(player.userId) === Number(room.currentUserId))?.seat ?? -1;
    }

    function propertyCard(space, state) {
        const level = Number(state.houses?.[space.index] || 0);
        const rent = space.rents?.[Math.min(level, 5)] ?? space.baseRent;
        const mortgaged = state.mortgaged?.includes(space.index) ? ' · İpotekli' : '';
        return `<li><strong>${escapeHtml(space.name)}</strong><small>${level===5?'Otel':level?level+' bina':'Arsa'} · Kira ${money(rent)}${space.buildCost?` · İnşa ${money(space.buildCost)}`:''}${mortgaged}</small></li>`;
    }

    function replaceOptions(select, options, emptyText) {
        const selected = select.value;
        select.innerHTML = options.length
            ? options.map(option => `<option value="${option.value}">${escapeHtml(option.label)}</option>`).join('')
            : `<option value="">${escapeHtml(emptyText)}</option>`;
        if (options.some(option => String(option.value) === selected)) select.value = selected;
        select.disabled = options.length === 0;
    }

    function renderManagement(room, spaces) {
        const state = room.state;
        const seat = currentSeat(room);
        const player = state.players[seat];
        const owned = (player?.properties || []).map(index => spaces.get(index)).filter(Boolean);
        replaceOptions(propertySelect, owned.map(space => ({
            value: space.index,
            label: `${space.name}${state.mortgaged?.includes(space.index) ? ' (ipotekli)' : ''}`,
        })), 'Yönetilecek mülk yok');

        const isMyTurn = state.turn === seat && state.phase === 'playing';
        document.getElementById('monopoly-build').disabled = !isMyTurn || owned.length === 0;
        document.getElementById('monopoly-mortgage').disabled = !isMyTurn || owned.length === 0;

        const recipients = state.players.filter(candidate => candidate.seat !== seat && !candidate.bankrupt);
        replaceOptions(tradePlayerSelect, recipients.map(candidate => ({value:candidate.seat,label:candidate.name})), 'Uygun oyuncu yok');
        tradeOfferButton.disabled = Boolean(state.trade) || recipients.length === 0 || player?.bankrupt;

        if (!state.trade) {
            tradeStatus.hidden = true;
            return;
        }

        const trade = state.trade;
        const from = state.players[trade.from]?.name || 'Oyuncu';
        const to = state.players[trade.to]?.name || 'Oyuncu';
        document.getElementById('monopoly-trade-text').textContent = `${from}, ${to} oyuncusuna ${money(trade.cashGive)} verip ${money(trade.cashWant)} istiyor.`;
        tradeStatus.hidden = false;
        tradeResponse.hidden = trade.to !== seat;
    }

    function render(room) {
        const state = room.state;
        const spaces = new Map(state.spaces.map(space => [space.index, space]));
        playersElement.innerHTML = state.players.map(player => {
            const owned = (player.properties || []).map(index => spaces.get(index)).filter(Boolean);
            return `<article class="mono-player-card"><p><span class="token" style="--token:${tokenColors[player.seat]}">${player.seat+1}</span> <strong>${escapeHtml(player.name)}</strong><br>${money(player.money)} · ${player.bankrupt?'İflas':'Kare '+player.position}</p><ul class="owned-properties">${owned.length?owned.map(space=>propertyCard(space,state)).join(''):'<li><small>Henüz mülk yok</small></li>'}</ul></article>`;
        }).join('');

        document.getElementById('die-one').textContent = state.dice[0] || '–';
        document.getElementById('die-two').textContent = state.dice[1] || '–';
        board.querySelectorAll('.mono-space').forEach(element => element.remove());
        state.spaces.forEach(space => {
            const element = document.createElement('div');
            const [row, column] = place(space.index);
            const owner = state.properties[space.index];
            const tokens = state.players.filter(player => player.position===space.index && !player.bankrupt).map(player=>`<span class="token" style="--token:${tokenColors[player.seat]}">${player.seat+1}</span>`).join('');
            element.className = 'mono-space' + ([0,10,20,30].includes(space.index) ? ' corner' : '');
            element.style.gridArea = `${row}/${column}`;
            element.innerHTML = `${space.group?`<i class="strip" style="background:${groupColors[space.group]}"></i>`:''}<b>${escapeHtml(space.name)}</b>${space.price?`<small>${money(space.price)}</small>`:''}<span>${owner===undefined?'':escapeHtml(state.players[owner].name)}</span><div>${tokens}</div>`;
            board.appendChild(element);
        });

        const auctionControls = document.getElementById('auction-controls');
        auctionControls.hidden = !state.auction;
        if (state.auction) {
            const input = document.getElementById('auction-amount');
            input.min = state.auction.bid + 1;
            input.value = state.auction.bid + 10;
        }
        renderManagement(room, spaces);

        const card = state.lastCard;
        if (card) {
            const key = `${room.version}-${card.seat}-${card.text}`;
            if (key !== lastCardKey) {
                lastCardKey = key;
                message.textContent = `${card.type==='chance'?'Şans':'Toplum Fonu'} · ${state.players[card.seat]?.name}: ${card.text}`;
                return;
            }
        }
        message.textContent = state.phase==='completed' ? `Kazanan: ${state.players[state.winnerSeat]?.name}` : state.pending!==null ? 'Mülkü satın al veya açık artırmaya çıkar.' : state.auction ? `Açık artırma ${money(state.auction.bid)} · Sıradaki ${state.players[state.auction.next]?.name}` : `Sıra ${state.players[state.turn]?.name}`;
    }

    async function act(action, extra = {}) {
        if (busy) return;
        busy = true;
        try {
            const response = await fetch(root.dataset.actionUrl, {method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({action,...extra,[root.dataset.csrfName]:root.dataset.csrfHash})});
            const payload = await response.json();
            if (payload.csrfHash) root.dataset.csrfHash = payload.csrfHash;
            if (!response.ok) throw Error(payload.message);
            render(payload.room);
            document.dispatchEvent(new CustomEvent('project:four-player-state', {detail:{room:payload.room}}));
        } catch (error) {
            message.textContent = error.message;
        } finally {
            busy = false;
        }
    }

    document.querySelectorAll('[data-monopoly-action]').forEach(button => button.onclick = () => act(button.dataset.monopolyAction));
    document.getElementById('auction-bid').onclick = () => act('auction_bid', {amount:Number(document.getElementById('auction-amount').value)});
    document.getElementById('auction-fold').onclick = () => act('auction_bid', {amount:0});
    document.getElementById('monopoly-build').onclick = () => act('build', {space:Number(propertySelect.value)});
    document.getElementById('monopoly-mortgage').onclick = () => act('mortgage', {space:Number(propertySelect.value)});
    tradeOfferButton.onclick = () => act('trade_offer', {to:Number(tradePlayerSelect.value),cashGive:Number(document.getElementById('monopoly-cash-give').value),cashWant:Number(document.getElementById('monopoly-cash-want').value)});
    document.getElementById('monopoly-trade-accept').onclick = () => act('trade_response', {accept:true});
    document.getElementById('monopoly-trade-reject').onclick = () => act('trade_response', {accept:false});
    document.addEventListener('project:four-player-state', event => { if (event.detail?.room) render(event.detail.room); });
})();
