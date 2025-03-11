import { mount } from '@vue/test-utils';
import ProductCard from '../../resources/js/components/ProductCard.vue';

describe('ProductCard.vue', () => {
  const product = {
    id: 1,
    name: 'Pan Francés',
    description: 'Pan tradicional francés',
    price: 2.50,
    stock: 10,
    image: '/storage/products/pan-frances.jpg'
  };

  test('renders product information correctly', () => {
    const wrapper = mount(ProductCard, {
      props: { product }
    });

    expect(wrapper.find('h3').text()).toBe('Pan Francés');
    expect(wrapper.find('.description').text()).toBe('Pan tradicional francés');
    expect(wrapper.find('.price').text()).toBe('$2.50');
    expect(wrapper.find('img').attributes('src')).toBe('/storage/products/pan-frances.jpg');
    expect(wrapper.find('img').attributes('alt')).toBe('Pan Francés');
  });

  test('emits add-to-cart event when button is clicked', async () => {
    const wrapper = mount(ProductCard, {
      props: { product }
    });

    await wrapper.find('.add-to-cart-btn').trigger('click');
    
    expect(wrapper.emitted('add-to-cart')).toBeTruthy();
    expect(wrapper.emitted('add-to-cart')[0]).toEqual([product]);
  });

  test('button is disabled when product is out of stock', () => {
    const outOfStockProduct = { ...product, stock: 0 };
    const wrapper = mount(ProductCard, {
      props: { product: outOfStockProduct }
    });

    const button = wrapper.find('.add-to-cart-btn');
    expect(button.attributes('disabled')).toBeDefined();
    expect(button.text()).toBe('Sin stock');
  });

  test('formats price correctly', () => {
    const productWithLongPrice = { ...product, price: 2.5 };
    const wrapper = mount(ProductCard, {
      props: { product: productWithLongPrice }
    });

    expect(wrapper.find('.price').text()).toBe('$2.50');
  });
});