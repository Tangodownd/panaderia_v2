<template>
    <div class="shopping-cart">
      <h2>Carrito de Compras</h2>
      
      <div v-if="items.length === 0" class="empty-cart">
        <p>Tu carrito está vacío</p>
        <button @click="$emit('continue-shopping')" class="continue-btn">Continuar comprando</button>
      </div>
      
      <div v-else>
        <div class="cart-items">
          <div v-for="(item, index) in items" :key="index" class="cart-item">
            <img :src="item.product.image" :alt="item.product.name" class="item-image">
            <div class="item-details">
              <h3>{{ item.product.name }}</h3>
              <p>${{ formatPrice(item.product.price) }}</p>
            </div>
            <div class="item-quantity">
              <button @click="updateQuantity(index, -1)" :disabled="item.quantity <= 1">-</button>
              <span>{{ item.quantity }}</span>
              <button @click="updateQuantity(index, 1)" :disabled="item.quantity >= item.product.stock">+</button>
            </div>
            <div class="item-total">
              ${{ formatPrice(item.product.price * item.quantity) }}
            </div>
            <button @click="removeItem(index)" class="remove-btn">×</button>
          </div>
        </div>
        
        <div class="cart-summary">
          <div class="summary-row">
            <span>Subtotal:</span>
            <span>${{ formatPrice(subtotal) }}</span>
          </div>
          <div class="summary-row">
            <span>Impuestos (16%):</span>
            <span>${{ formatPrice(tax) }}</span>
          </div>
          <div class="summary-row total">
            <span>Total:</span>
            <span>${{ formatPrice(total) }}</span>
          </div>
          
          <div class="cart-actions">
            <button @click="$emit('continue-shopping')" class="continue-btn">Continuar comprando</button>
            <button @click="checkout" class="checkout-btn">Proceder al pago</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'ShoppingCart',
    props: {
      items: {
        type: Array,
        required: true
      }
    },
    computed: {
      subtotal() {
        return this.items.reduce((sum, item) => {
          return sum + (item.product.price * item.quantity);
        }, 0);
      },
      tax() {
        return this.subtotal * 0.16;
      },
      total() {
        return this.subtotal + this.tax;
      }
    },
    methods: {
      formatPrice(price) {
        return price.toFixed(2);
      },
      updateQuantity(index, change) {
        const newQuantity = this.items[index].quantity + change;
        if (newQuantity > 0 && newQuantity <= this.items[index].product.stock) {
          this.$emit('update-quantity', { index, quantity: newQuantity });
        }
      },
      removeItem(index) {
        this.$emit('remove-item', index);
      },
      checkout() {
        this.$emit('checkout', this.items);
      }
    }
  }
  </script>
  
  <style scoped>
  .shopping-cart {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  h2 {
    margin-top: 0;
    color: #1d3557;
    border-bottom: 2px solid #f1faee;
    padding-bottom: 10px;
  }
  
  .empty-cart {
    text-align: center;
    padding: 40px 0;
  }
  
  .cart-items {
    margin-bottom: 20px;
  }
  
  .cart-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f1faee;
  }
  
  .item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 15px;
  }
  
  .item-details {
    flex: 1;
  }
  
  .item-details h3 {
    margin: 0 0 5px 0;
    font-size: 1.1em;
  }
  
  .item-quantity {
    display: flex;
    align-items: center;
    margin: 0 20px;
  }
  
  .item-quantity button {
    width: 30px;
    height: 30px;
    background: #f1faee;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .item-quantity button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .item-quantity span {
    margin: 0 10px;
    min-width: 20px;
    text-align: center;
  }
  
  .item-total {
    font-weight: bold;
    min-width: 80px;
    text-align: right;
  }
  
  .remove-btn {
    background: none;
    border: none;
    color: #e63946;
    font-size: 1.5em;
    cursor: pointer;
    padding: 0 10px;
  }
  
  .cart-summary {
    background: #f1faee;
    padding: 15px;
    border-radius: 4px;
  }
  
  .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
  }
  
  .total {
    font-weight: bold;
    font-size: 1.2em;
    border-top: 1px solid #ddd;
    padding-top: 10px;
  }
  
  .cart-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
  }
  
  .continue-btn, .checkout-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
  }
  
  .continue-btn {
    background: white;
    color: #457b9d;
    border: 1px solid #457b9d;
  }
  
  .checkout-btn {
    background: #457b9d;
    color: white;
  }
  
  .continue-btn:hover {
    background: #f8f9fa;
  }
  
  .checkout-btn:hover {
    background: #1d3557;
  }
  </style>