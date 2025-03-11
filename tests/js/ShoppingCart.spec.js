import { mount } from '@vue/test-utils';
import ShoppingCart from '../../resources/js/components/ShoppingCart.vue';

describe('ShoppingCart.vue', () => {
  const createItems = () => [
    {
      product: {
        id: 1,
        name: 'Pan Francés',
        price: 2.50,
        stock: 10,
        image: '/storage/products/pan-frances.jpg'
      },
      quantity: 2
    },
    {
      product: {
        id: 2,
        name: 'Croissant',
        price: 3.75,
        stock: 5,
        image: '/storage/products/croissant.jpg'
      },
      quantity: 1
    }
  ];

  test('renders empty cart message when no items', () => {
    const wrapper = mount(ShoppingCart, {
      props: { items: [] }
    });

    expect(wrapper.find('.empty-cart').exists()).toBe(true);
    expect(wrapper.find('.empty-cart p').text()).toBe('Tu carrito está vacío');
  });

  test('renders cart items correctly', () => {
    const items = createItems();
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const cartItems = wrapper.findAll('.cart-item');
    expect(cartItems.length).toBe(2);
    
    expect(cartItems[0].find('h3').text()).toBe('Pan Francés');
    expect(cartItems[0].find('.item-quantity span').text()).toBe('2');
    expect(cartItems[0].find('.item-total').text()).toBe('$5.00');
    
    expect(cartItems[1].find('h3').text()).toBe('Croissant');
    expect(cartItems[1].find('.item-quantity span').text()).toBe('1');
    expect(cartItems[1].find('.item-total').text()).toBe('$3.75');
  });

  test('calculates subtotal, tax and total correctly', () => {
    const items = createItems();
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const summaryRows = wrapper.findAll('.summary-row');
    expect(summaryRows[0].text()).toContain('$8.75'); // Subtotal
    expect(summaryRows[1].text()).toContain('$1.40'); // Tax (16% of 8.75)
    expect(summaryRows[2].text()).toContain('$10.15'); // Total
  });

  test('emits update-quantity event when quantity is changed', async () => {
    const items = createItems();
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const increaseButton = wrapper.findAll('.item-quantity button')[1]; // + button for first item
    await increaseButton.trigger('click');
    
    expect(wrapper.emitted('update-quantity')).toBeTruthy();
    expect(wrapper.emitted('update-quantity')[0]).toEqual([{ index: 0, quantity: 3 }]);
  });

  test('emits remove-item event when remove button is clicked', async () => {
    const items = createItems();
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const removeButton = wrapper.findAll('.remove-btn')[0];
    await removeButton.trigger('click');
    
    expect(wrapper.emitted('remove-item')).toBeTruthy();
    expect(wrapper.emitted('remove-item')[0]).toEqual([0]);
  });

  test('emits checkout event with items when checkout button is clicked', async () => {
    const items = createItems();
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const checkoutButton = wrapper.find('.checkout-btn');
    await checkoutButton.trigger('click');
    
    expect(wrapper.emitted('checkout')).toBeTruthy();
    expect(wrapper.emitted('checkout')[0]).toEqual([items]);
  });

  test('disables decrease button when quantity is 1', () => {
    const items = [
      {
        product: { id: 1, name: 'Pan', price: 2.50, stock: 10, image: '/img.jpg' },
        quantity: 1
      }
    ];
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const decreaseButton = wrapper.find('.item-quantity button');
    expect(decreaseButton.attributes('disabled')).toBeDefined();
  });

  test('disables increase button when quantity equals stock', () => {
    const items = [
      {
        product: { id: 1, name: 'Pan', price: 2.50, stock: 5, image: '/img.jpg' },
        quantity: 5
      }
    ];
    const wrapper = mount(ShoppingCart, {
      props: { items }
    });

    const increaseButton = wrapper.findAll('.item-quantity button')[1];
    expect(increaseButton.attributes('disabled')).toBeDefined();
  });
});