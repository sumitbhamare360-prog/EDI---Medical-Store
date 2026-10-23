// MediStore POS JavaScript (vanilla, no libraries)
// Handles medicine search (debounce 300ms), cart add/update/remove, running total.

const searchInput = document.getElementById('med-search');
const resultsEl = document.getElementById('search-results');
const cartBody = document.getElementById('cart-body');
const totalEl = document.getElementById('cart-total');
const checkoutBtn = document.getElementById('checkout-btn');
const rxNote = document.getElementById('rx-note');

let cart = []; // {medicine_id, name, price, qty, requires_prescription}
let debounceTimer;

function escapeHtml(t){ return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function renderCart(){
    cartBody.innerHTML = '';
    let total = 0;
    let needsRx = false;
    cart.forEach((item, idx) => {
        const lineTotal = item.price * item.qty;
        total += lineTotal;
        if(item.requires_prescription) needsRx = true;
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td>'+escapeHtml(item.name)+'</td>'+
            '<td>'+parseFloat(item.price).toFixed(2)+'</td>'+
            '<td><input type="number" min="1" value="'+item.qty+'" onchange="updateQty('+idx+', this.value)"></td>'+
            '<td>'+parseFloat(lineTotal).toFixed(2)+'</td>'+
            '<td>'+(item.requires_prescription?'<span class="badge rx-badge">Rx</span>':'-')+'</td>'+
            '<td><button class="btn small ghost" onclick="removeItem('+idx+')">Remove</button></td>';
        cartBody.appendChild(tr);
    });
    totalEl.textContent = parseFloat(total).toFixed(2);
    checkoutBtn.disabled = cart.length === 0;
    rxNote.style.display = needsRx ? 'block' : 'none';
}

window.cartJSON = function(){
    return JSON.stringify(cart);
};

window.updateQty = function(idx, val){
    let q = parseInt(val, 10);
    if(isNaN(q) || q < 1) q = 1;
    cart[idx].qty = q;
    renderCart();
};

window.removeItem = function(idx){
    cart.splice(idx, 1);
    renderCart();
};

function addToCart(med){
    const existing = cart.findIndex(i => i.medicine_id === med.medicine_id);
    if(existing >= 0){
        cart[existing].qty += 1;
    } else {
        cart.push({
            medicine_id: med.medicine_id,
            name: med.name,
            price: parseFloat(med.selling_price),
            qty: 1,
            requires_prescription: parseInt(med.requires_prescription, 10) === 1
        });
    }
    renderCart();
}

function renderResults(list){
    resultsEl.innerHTML = '';
    if(list.length === 0){
        resultsEl.innerHTML = '<div class="search-item">No medicines found.</div>';
        return;
    }
    list.forEach(m => {
        const usable = parseInt(m.usable_stock, 10);
        const div = document.createElement('div');
        div.className = 'search-item';
        div.innerHTML =
            '<div class="si-main">'+
            '<strong>'+escapeHtml(m.name)+'</strong>'+
            (m.generic_name?' <span style="color:#666;">('+escapeHtml(m.generic_name)+')</span>':'')+
            '</div>'+
            '<div class="si-meta">'+
            'Price: $'+parseFloat(m.selling_price).toFixed(2)+' &nbsp;|&nbsp; Stock: '+usable+
            (usable<=0?' <span style="color:#b00;">(out of stock)</span>':'')+
            (parseInt(m.requires_prescription,10)?' <span class="badge rx-badge">Rx Required</span>':'')+
            '</div>';
        if(usable > 0){
            const btn = document.createElement('button');
            btn.className = 'btn';
            btn.textContent = 'Add';
            btn.onclick = () => { addToCart(m); resultsEl.innerHTML=''; searchInput.value=''; };
            div.appendChild(btn);
        }
        resultsEl.appendChild(div);
    });
}

searchInput.addEventListener('input', function(){
    clearTimeout(debounceTimer);
    const q = this.value.trim();
    resultsEl.innerHTML = '';
    if(q.length < 2) return;
    debounceTimer = setTimeout(() => {
        fetch('search.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(renderResults)
            .catch(() => { resultsEl.innerHTML='<div class="search-item">Search error.</div>'; });
    }, 300);
});

// Initial cart render
renderCart();
