<template>
    <div class="product-card">
      <img :src="product.image" :alt="product.name" class="product-image">
      <div class="product-info">
        <h3>{{ product.name }}</h3>
        <p class="description">{{ product.description }}</p>
        <p class="price">${{ formatPrice(product.price) }}</p>
        <div class="actions">
          <button 
            @click="addToCart" 
            class="add-to-cart-btn"
            :disabled="!product.stock"
          >
            {{ product.stock ? 'Añadir al carrito' : 'Sin stock' }}
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'ProductCard',
    props: {
      product: {
        type: Object,
        required: true
      }
    },
    methods: {
      formatPrice(price) {
        return price.toFixed(2);
      },
      addToCart() {
        if (this.product.stock > 0) {
          this.$emit('add-to-cart', this.product);
        }
      }
    }
  }
  </script>
  
  <style scoped>
  .product-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  }
  
  .product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
  }
  
  .product-info {
    padding: 15px;
  }
  
  .description {
    color: #666;
    margin: 10px 0;
  }
  
  .price {
    font-weight: bold;
    color: #e63946;
    font-size: 1.2em;
  }
  
  .actions {
    margin-top: 15px;
  }
  
  .add-to-cart-btn {
    background-color: #457b9d;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  
  .add-to-cart-btn:hover:not(:disabled) {
    background-color: #1d3557;
  }
  
  .add-to-cart-btn:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
  }
  </style>