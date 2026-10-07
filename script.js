const form = document.getElementById('orderForm');
const orderMessage = document.getElementById('orderMessage');

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(form);
  orderMessage.textContent = 'Enregistrement de la commande en cours...';

  try {
    const response = await fetch('save_order.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      orderMessage.textContent = result.message;
      form.reset();
    } else {
      orderMessage.textContent = result.message;
    }
  } catch (error) {
    orderMessage.textContent = 'Une erreur est survenue lors de l’envoi de la commande.';
  }
});
