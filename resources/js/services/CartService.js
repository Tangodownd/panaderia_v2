export default class CartService {
    constructor() {
      this.storageKey = 'panaderia_cart';
      this.taxRate = 0.16;
    }
  
    getCart() {
      const cartData = localStorage.getItem(this.storageKey);
      return cartData ? JSON.parse(cartData) : [];
    }
  
    saveCart(cart) {
      localStorage.setItem(this.storageKey, JSON.stringify(cart));
    }
  
    addToCart(product, quantity = 1) {
      const cart = this.getCart();
      const existingItemIndex = cart.findIndex(item => item.product.id === product.id);
  
      if (existingItemIndex >= 0) {
        // El producto ya está en el carrito, actualizar cantidad
        const newQuantity = cart[existingItemIndex].quantity + quantity;
        
        // Verificar stock
        if (newQuantity > product.stock) {
          return {
            success: false,
            message: `No hay suficiente stock. Stock disponible: ${product.stock}`
          };
        }
        
        cart[existingItemIndex].quantity = newQuantity;
      } else {
        // Nuevo producto en el carrito
        if (quantity > product.stock) {
          return {
            success: false,
            message: `No hay suficiente stock. Stock disponible: ${product.stock}`
          };
        }
        
        cart.push({ product, quantity });
      }
  
      this.saveCart(cart);
      return { success: true, cart };
    }
  
    updateQuantity(index, quantity) {
      const cart = this.getCart();
      
      if (index < 0 || index >= cart.length) {
        return {
          success: false,
          message: 'Ítem no encontrado en el carrito'
        };
      }
      
      if (quantity <= 0) {
        return {
          success: false,
          message: 'La cantidad debe ser mayor a cero'
        };
      }
      
      if (quantity > cart[index].product.stock) {
        return {
          success: false,
          message: `No hay suficiente stock. Stock disponible: ${cart[index].product.stock}`
        };
      }
      
      cart[index].quantity = quantity;
      this.saveCart(cart);
      
      return { success: true, cart };
    }
  
    removeItem(index) {
      const cart = this.getCart();
      
      if (index < 0 || index >= cart.length) {
        return {
          success: false,
          message: 'Ítem no encontrado en el carrito'
        };
      }
      
      cart.splice(index, 1);
      this.saveCart(cart);
      
      return { success: true, cart };
    }
  
    clearCart() {
      localStorage.removeItem(this.storageKey);
      return { success: true, cart: [] };
    }
  
    calculateSubtotal(cart) {
      return cart.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
    }
  
    calculateTax(subtotal) {
      return subtotal * this.taxRate;
    }
  
    calculateTotal(cart) {
      const subtotal = this.calculateSubtotal(cart);
      const tax = this.calculateTax(subtotal);
      return subtotal + tax;
    }
  }