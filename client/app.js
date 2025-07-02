const backendUrl = "http://localhost:8000";

document.getElementById('register-form').addEventListener('submit', async (e) => {
  e.preventDefault();

  const email = document.getElementById('register-email').value;
  const password = document.getElementById('register-password').value;

  const response = await fetch(`${backendUrl}/user`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });

  const messageEl = document.getElementById('register-message');

  if (response.ok) {
    messageEl.style.color = 'green';
    messageEl.textContent = "Registration successful!";
  } else {
    const error = await response.json();
    messageEl.style.color = 'red';
    messageEl.textContent = error.error || 'Registration failed';
  }
});

document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();

  const email = document.getElementById('login-email').value;
  const password = document.getElementById('login-password').value;

  const response = await fetch(`${backendUrl}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });

  const messageEl = document.getElementById('login-message');

  if (response.ok) {
    const data = await response.json();
    messageEl.style.color = 'green';
    messageEl.textContent = "Login successful!";

    // Show products section after login
    document.getElementById('products-section').style.display = 'block';
  } else {
    const error = await response.json();
    messageEl.style.color = 'red';
    messageEl.textContent = error.error || 'Login failed';
  }
});

document.getElementById('fetch-products-btn').addEventListener('click', async () => {
  const response = await fetch(`${backendUrl}/product`, {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' }
  });

  const productsList = document.getElementById('products-list');
  productsList.innerHTML = '';

  if (response.ok) {
    const products = await response.json();
    if (products.length === 0) {
      productsList.innerHTML = '<li>No products found</li>';
      return;
    }
    products.forEach(prod => {
      const li = document.createElement('li');
      li.textContent = `${prod.name} - $${prod.price}`;
      productsList.appendChild(li);
    });
  } else {
    productsList.innerHTML = '<li>Failed to load products.</li>';
  }
});
