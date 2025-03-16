describe('Proceso de Checkout', () => {
  beforeEach(() => {
    // IMPORTANTE: Primero interceptar las llamadas a la API
    cy.intercept('GET', '/api/products*').as('getProducts')
    cy.intercept('POST', '/api/cart/add').as('addToCart')
    cy.intercept('GET', '/api/cart').as('getCart')
    cy.intercept('POST', '/api/orders').as('createOrder')
    
    // Después visitar la página principal
    cy.visit('/')
    
    // Esperar a que los productos se carguen
    cy.wait('@getProducts', { timeout: 10000 })
  })
  
  it('permite añadir productos al carrito y completar una orden', () => {
    // Añadir un producto al carrito - usando el selector correcto para el botón
    cy.contains('.product-card', 'Pan Francés')
      .contains('button', 'Añadir')
      .click()
    
    cy.wait('@addToCart')
    
    // Añadir otro producto al carrito
    cy.contains('.product-card', 'Croissant')
      .contains('button', 'Añadir')
      .click()
    
    cy.wait('@addToCart')
    
    // Abrir el carrito - usando la clase correcta del botón
    cy.get('.btn-cart').click()
    
    // Verificar que los productos estén en el carrito - usando un selector más específico
    // Primero verificamos que el modal del carrito esté visible
    cy.get('.custom-modal-content').should('be.visible')
    
    // Luego verificamos los productos dentro del carrito
    // Usamos un selector más específico para los items del carrito
    cy.get('.custom-modal-body .list-group .list-group-item').should('have.length', 2)
    
    // Verificar productos específicos
    cy.get('.custom-modal-body').contains('Pan Francés').should('exist')
    cy.get('.custom-modal-body').contains('Croissant').should('exist')
    
    // Proceder al checkout - usando el texto exacto del botón
    cy.contains('button', 'Proceder al pago').click()
    
    // Completar el formulario de checkout
    cy.get('#name').type('Juan Pérez')
    cy.get('#email').type('juan@example.com')
    
    // Seleccionar el código de país - usando el texto visible en lugar del valor
    cy.get('.input-group select').select('Venezuela (+58)')
    
    cy.get('#phone').type('4121234567')
    cy.get('#shipping_address').type('Calle Principal #123')
    cy.get('#payment_cash').check()
    cy.get('#notes').type('Entregar en la tarde')
    
    // Enviar el formulario
    cy.contains('button', 'Confirmar pedido').click()
    
    cy.wait('@createOrder')
    
    // Verificar que se muestre el mensaje de éxito
    cy.contains('¡Pedido completado con éxito!').should('be.visible')
    cy.contains('Tu número de pedido es:').should('be.visible')
    
    // Cerrar el modal
    cy.contains('button', 'Continuar comprando').click()
    
    // Verificar que el carrito esté vacío
    cy.get('.btn-cart').click()
    cy.contains('Tu carrito está vacío').should('be.visible')
  })
})