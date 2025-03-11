import CartService from '../../../resources/js/services/CartService';

// Mock localStorage
const localStorageMock = (() => {
  let store = {};
  return {
    getItem: jest.fn(key => store[key] || null),
    setItem: jest.fn((key, value) => {
      store[key] = value.toString();
    }),
    removeItem: jest.fn(key => {
      delete store[key];
    }),
    clear: jest.fn(() => {
      store = {};
    })
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
});

describe('CartService', () => {
  let cartService;
  
  beforeEach(() => {
    cartService = new CartService();
    localStorageMock.clear();
    jest.clearAllMocks();
  });
  
  describe('getCart', () => {
    test('returns empty array when cart is not in localStorage', () => {
      const cart = cartService.getCart();
      expect(cart).toEqual([]);
      expect(localStorageMock.getItem).toHaveBeenCalledWith('panaderia_cart');
    });
    
    test('returns parsed cart data when cart exists in localStorage', () => {
      const mockCart = [{ product: { id: 1 }, quantity: 2 }];
      localStorageMock.setItem('panaderia_cart', JSON.stringify(mockCart));
      
      const cart = cartService.getCart();
      expect(cart).toEqual(mockCart);
    });
  });
  
  describe('addToCart', () => {
    test('adds new product to empty cart', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      
      const result = cartService.addToCart(product, 2);
      
      expect(result.success).toBe(true);
      expect(result.cart).toEqual([{ product, quantity: 2 }]);
      expect(localStorageMock.setItem).toHaveBeenCalledWith(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 2 }])
      );
    });
    
    test('increases quantity when product already in cart', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      localStorageMock.setItem(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 2 }])
      );
      
      const result = cartService.addToCart(product, 3);
      
      expect(result.success).toBe(true);
      expect(result.cart).toEqual([{ product, quantity: 5 }]);
    });
    
    test('fails when adding more than available stock (new product)', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      
      const result = cartService.addToCart(product, 15);
      
      expect(result.success).toBe(false);
      expect(result.message).toContain('No hay suficiente stock');
      expect(localStorageMock.setItem).not.toHaveBeenCalled();
    });
    
    test('fails when adding more than available stock (existing product)', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      localStorageMock.setItem(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 8 }])
      );
      
      const result = cartService.addToCart(product, 3);
      
      expect(result.success).toBe(false);
      expect(result.message).toContain('No hay suficiente stock');
    });
  });
  
  describe('updateQuantity', () => {
    test('updates quantity of existing item', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      localStorageMock.setItem(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 2 }])
      );
      
      const result = cartService.updateQuantity(0, 5);
      
      expect(result.success).toBe(true);
      expect(result.cart[0].quantity).toBe(5);
    });
    
    test('fails when index is out of bounds', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      localStorageMock.setItem(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 2 }])
      );
      
      const result = cartService.updateQuantity(1, 5);
      
      expect(result.success).toBe(false);
      expect(result.message).toContain('Ítem no encontrado');
    });
    
    test('fails when quantity is less than or equal to zero', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      localStorageMock.setItem(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 2 }])
      );
      
      const result = cartService.updateQuantity(0, 0);
      
      expect(result.success).toBe(false);
      expect(result.message).toContain('La cantidad debe ser mayor a cero');
    });
    
    test('fails when quantity exceeds stock', () => {
      const product = { id: 1, name: 'Pan', price: 2.5, stock: 10 };
      localStorageMock.setItem(
        'panaderia_cart',
        JSON.stringify([{ product, quantity: 2 }])
      );
      
      const result = cartService.updateQuantity(0, 15);
      
      expect(result.success).toBe(false);
      expect(result.message).toContain('No hay suficiente stock');
    });
  });
  
  describe('calculation methods', () => {
    test('calculateSubtotal returns correct sum', () => {
      const cart = [
        { product: { price: 10 }, quantity: 2 },
        { product: { price: 5 }, quantity: 3 }
      ];
      
      const subtotal = cartService.calculateSubtotal(cart);
      
      expect(subtotal).toBe(35); // (10*2) + (5*3)
    });
    
    test('calculateTax returns correct tax amount', () => {
      const tax = cartService.calculateTax(100);
      
      expect(tax).toBe(16); // 100 * 0.16
    });
    
    test('calculateTotal returns correct total', () => {
      const cart = [
        { product: { price: 10 }, quantity: 2 },
        { product: { price: 5 }, quantity: 3 }
      ];
      
      const total = cartService.calculateTotal(cart);
      
      expect(total).toBe(40.6); // 35 + (35 * 0.16)
    });
  });
});