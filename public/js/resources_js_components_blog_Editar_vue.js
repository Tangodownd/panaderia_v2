"use strict";
(self["webpackChunkpanaderia"] = self["webpackChunkpanaderia"] || []).push([["resources_js_components_blog_Editar_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=script&lang=js":
/*!*****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.mjs");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_1__);



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  setup() {
    const router = (0,vue_router__WEBPACK_IMPORTED_MODULE_2__.useRouter)();
    const route = (0,vue_router__WEBPACK_IMPORTED_MODULE_2__.useRoute)();
    const blog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      titulo: '',
      contenido: '',
      category_id: '',
      precio: 0,
      descuento: 0,
      valoracion: 0,
      stock: 0,
      etiquetas: [],
      brand: '',
      sku: '',
      weight: null,
      dimensions: {
        width: null,
        height: null,
        depth: null
      },
      warrantyInformation: '',
      shippingInformation: '',
      availabilityStatus: 'In Stock',
      returnPolicy: '',
      minimumOrderQuantity: 1,
      thumbnail: null
    });
    const categories = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    const etiquetasString = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)({
      get: () => blog.etiquetas.join(', '),
      set: val => {
        blog.etiquetas = val.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
      }
    });
    const isLoading = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(true);
    const cargarBlog = async () => {
      isLoading.value = true;
      try {
        const response = await axios__WEBPACK_IMPORTED_MODULE_1___default().get(`/api/blog/${route.params.id}`);
        Object.assign(blog, response.data);
        blog.dimensions = blog.dimensions || {
          width: null,
          height: null,
          depth: null
        };
        blog.etiquetas = blog.etiquetas || [];
        blog.minimumOrderQuantity = blog.minimumOrderQuantity || 1;
        blog.availabilityStatus = blog.availabilityStatus || 'In Stock';
      } catch (error) {
        console.error('Error al cargar el blog:', error);
      } finally {
        isLoading.value = false;
      }
    };
    const fetchCategories = async () => {
      try {
        const response = await axios__WEBPACK_IMPORTED_MODULE_1___default().get('/api/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Error al cargar las categorías:', error);
      }
    };
    const handleFileUpload = event => {
      blog.thumbnail = event.target.files[0];
    };
    const actualizar = async () => {
      try {
        const formData = new FormData();
        for (const key in blog) {
          if (key === 'dimensions' || key === 'etiquetas') {
            formData.append(key, JSON.stringify(blog[key]));
          } else if (key === 'thumbnail' && blog[key] instanceof File) {
            formData.append(key, blog[key]);
          } else if (key !== 'thumbnail') {
            formData.append(key, blog[key]);
          }
        }
        await axios__WEBPACK_IMPORTED_MODULE_1___default().post(`/api/blog/${blog.id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-HTTP-Method-Override': 'PUT'
          }
        });
        router.push({
          name: 'mostrarBlogs'
        });
      } catch (error) {
        console.error('Error al actualizar el blog:', error);
      }
    };
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(() => {
      cargarBlog();
      fetchCategories();
    });
    return {
      blog,
      categories,
      etiquetasString,
      actualizar,
      handleFileUpload,
      isLoading
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=template&id=9df05c26&scoped=true":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=template&id=9df05c26&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

const _hoisted_1 = {
  class: "container-fluid py-4 bg-beige"
};
const _hoisted_2 = {
  class: "row justify-content-center"
};
const _hoisted_3 = {
  class: "col-md-8"
};
const _hoisted_4 = {
  class: "card bg-white shadow-lg rounded-lg overflow-hidden"
};
const _hoisted_5 = {
  class: "card-body"
};
const _hoisted_6 = {
  key: 0,
  class: "text-center"
};
const _hoisted_7 = {
  class: "mb-3"
};
const _hoisted_8 = {
  class: "mb-3"
};
const _hoisted_9 = {
  class: "row mb-3"
};
const _hoisted_10 = {
  class: "col-md-2"
};
const _hoisted_11 = ["value"];
const _hoisted_12 = {
  class: "col-md-2"
};
const _hoisted_13 = {
  class: "col-md-2"
};
const _hoisted_14 = {
  class: "col-md-2"
};
const _hoisted_15 = {
  class: "col-md-2"
};
const _hoisted_16 = {
  class: "mb-3"
};
const _hoisted_17 = {
  class: "row mb-3"
};
const _hoisted_18 = {
  class: "col-md-4"
};
const _hoisted_19 = {
  class: "col-md-4"
};
const _hoisted_20 = {
  class: "col-md-4"
};
const _hoisted_21 = {
  class: "mb-3"
};
const _hoisted_22 = {
  class: "row"
};
const _hoisted_23 = {
  class: "col"
};
const _hoisted_24 = {
  class: "col"
};
const _hoisted_25 = {
  class: "col"
};
const _hoisted_26 = {
  class: "mb-3"
};
const _hoisted_27 = {
  class: "mb-3"
};
const _hoisted_28 = {
  class: "mb-3"
};
const _hoisted_29 = {
  class: "mb-3"
};
const _hoisted_30 = {
  class: "mb-3"
};
const _hoisted_31 = {
  class: "mb-3"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [_cache[42] || (_cache[42] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    class: "card-header bg-brown text-white"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", {
    class: "mb-0 font-bold"
  }, "Editar Producto")], -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [$setup.isLoading ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, _cache[21] || (_cache[21] = [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    class: "spinner-border text-brown",
    role: "status"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
    class: "visually-hidden"
  }, "Cargando...")], -1 /* HOISTED */)]))) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("form", {
    key: 1,
    onSubmit: _cache[20] || (_cache[20] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => $setup.actualizar && $setup.actualizar(...args), ["prevent"])),
    enctype: "multipart/form-data"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [_cache[22] || (_cache[22] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "titulo",
    class: "form-label text-brown"
  }, "Nombre del Producto", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => $setup.blog.titulo = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "titulo",
    required: ""
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.titulo]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [_cache[23] || (_cache[23] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "contenido",
    class: "form-label text-brown"
  }, "Descripción del Producto", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("textarea", {
    "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => $setup.blog.contenido = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "contenido",
    rows: "3",
    required: ""
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.contenido]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [_cache[24] || (_cache[24] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "categoria",
    class: "form-label text-brown"
  }, "Categoría", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("select", {
    "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => $setup.blog.category_id = $event),
    class: "form-select border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "categoria",
    required: ""
  }, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.categories, category => {
    return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("option", {
      key: category.id,
      value: category.id
    }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(category.name), 9 /* TEXT, PROPS */, _hoisted_11);
  }), 128 /* KEYED_FRAGMENT */))], 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelSelect, $setup.blog.category_id]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [_cache[25] || (_cache[25] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "precio",
    class: "form-label text-brown"
  }, "Precio", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[3] || (_cache[3] = $event => $setup.blog.precio = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "precio",
    step: "0.01",
    min: "0",
    required: ""
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.precio]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_13, [_cache[26] || (_cache[26] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "descuento",
    class: "form-label text-brown"
  }, "Descuento (%)", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => $setup.blog.descuento = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "descuento",
    min: "0",
    max: "100",
    required: ""
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.descuento]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [_cache[27] || (_cache[27] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "valoracion",
    class: "form-label text-brown"
  }, "Valoración", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => $setup.blog.valoracion = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "valoracion",
    min: "0",
    max: "5",
    step: "0.1",
    required: ""
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.valoracion]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_15, [_cache[28] || (_cache[28] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "stock",
    class: "form-label text-brown"
  }, "Stock", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[6] || (_cache[6] = $event => $setup.blog.stock = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "stock",
    min: "0",
    required: ""
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.stock]])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_16, [_cache[29] || (_cache[29] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "etiquetas",
    class: "form-label text-brown"
  }, "Etiquetas (separadas por comas)", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[7] || (_cache[7] = $event => $setup.etiquetasString = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "etiquetas"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.etiquetasString]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_17, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_18, [_cache[30] || (_cache[30] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "brand",
    class: "form-label text-brown"
  }, "Marca", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[8] || (_cache[8] = $event => $setup.blog.brand = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "brand"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.brand]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_19, [_cache[31] || (_cache[31] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "sku",
    class: "form-label text-brown"
  }, "SKU", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[9] || (_cache[9] = $event => $setup.blog.sku = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "sku"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.sku]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [_cache[32] || (_cache[32] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "weight",
    class: "form-label text-brown"
  }, "Peso (kg)", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[10] || (_cache[10] = $event => $setup.blog.weight = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "weight",
    step: "0.01",
    min: "0"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.weight]])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, [_cache[33] || (_cache[33] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    class: "form-label text-brown"
  }, "Dimensiones (cm)", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_22, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_23, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[11] || (_cache[11] = $event => $setup.blog.dimensions.width = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    placeholder: "Ancho",
    step: "0.01",
    min: "0"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.dimensions.width]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[12] || (_cache[12] = $event => $setup.blog.dimensions.height = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    placeholder: "Alto",
    step: "0.01",
    min: "0"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.dimensions.height]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_25, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[13] || (_cache[13] = $event => $setup.blog.dimensions.depth = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    placeholder: "Profundidad",
    step: "0.01",
    min: "0"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.dimensions.depth]])])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_26, [_cache[34] || (_cache[34] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "warrantyInformation",
    class: "form-label text-brown"
  }, "Información de Garantía", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[14] || (_cache[14] = $event => $setup.blog.warrantyInformation = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "warrantyInformation"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.warrantyInformation]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_27, [_cache[35] || (_cache[35] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "shippingInformation",
    class: "form-label text-brown"
  }, "Información de Envío", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[15] || (_cache[15] = $event => $setup.blog.shippingInformation = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "shippingInformation"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.shippingInformation]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_28, [_cache[37] || (_cache[37] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "availabilityStatus",
    class: "form-label text-brown"
  }, "Estado de Disponibilidad", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("select", {
    "onUpdate:modelValue": _cache[16] || (_cache[16] = $event => $setup.blog.availabilityStatus = $event),
    class: "form-select border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "availabilityStatus"
  }, _cache[36] || (_cache[36] = [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("option", {
    value: "In Stock"
  }, "En Stock", -1 /* HOISTED */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("option", {
    value: "Low Stock"
  }, "Bajo Stock", -1 /* HOISTED */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("option", {
    value: "Out of Stock"
  }, "Agotado", -1 /* HOISTED */)]), 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelSelect, $setup.blog.availabilityStatus]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_29, [_cache[38] || (_cache[38] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "returnPolicy",
    class: "form-label text-brown"
  }, "Política de Devolución", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "text",
    "onUpdate:modelValue": _cache[17] || (_cache[17] = $event => $setup.blog.returnPolicy = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "returnPolicy"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.returnPolicy]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_30, [_cache[39] || (_cache[39] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "minimumOrderQuantity",
    class: "form-label text-brown"
  }, "Cantidad Mínima de Pedido", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "number",
    "onUpdate:modelValue": _cache[18] || (_cache[18] = $event => $setup.blog.minimumOrderQuantity = $event),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "minimumOrderQuantity",
    min: "1"
  }, null, 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $setup.blog.minimumOrderQuantity]])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_31, [_cache[40] || (_cache[40] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", {
    for: "thumbnail",
    class: "form-label text-brown"
  }, "Imagen del Producto", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
    type: "file",
    onChange: _cache[19] || (_cache[19] = (...args) => $setup.handleFileUpload && $setup.handleFileUpload(...args)),
    class: "form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50",
    id: "thumbnail",
    accept: "image/*"
  }, null, 32 /* NEED_HYDRATION */)]), _cache[41] || (_cache[41] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    type: "submit",
    class: "btn bg-brown text-beige hover:bg-light-beige"
  }, "Guardar Cambios", -1 /* HOISTED */))], 32 /* NEED_HYDRATION */))])])])])]);
}

/***/ }),

/***/ "./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/laravel-mix/node_modules/css-loader/dist/runtime/api.js */ "./node_modules/laravel-mix/node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_laravel_mix_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, "\n#blogsTable[data-v-9df05c26] {\n  width: 100%;\n}\n.table-responsive[data-v-9df05c26] {\n  overflow-x: auto;\n}\n.btn-group[data-v-9df05c26] {\n  white-space: nowrap;\n}\n.bg-brown[data-v-9df05c26] {\n  background-color: #8D6E63;\n}\n.text-brown[data-v-9df05c26] {\n  color: #5D4037;\n}\n.border-brown[data-v-9df05c26] {\n  border-color: #8D6E63;\n}\n.btn-brown[data-v-9df05c26] {\n  background-color: #8D6E63;\n  border-color: #8D6E63;\n  color: #F5E6D3;\n}\n.btn-brown[data-v-9df05c26]:hover {\n  background-color: #795548;\n  border-color: #795548;\n  color: #F5E6D3;\n}\n.btn-outline-brown[data-v-9df05c26] {\n  color: #8D6E63;\n  border-color: #8D6E63;\n}\n.btn-outline-brown[data-v-9df05c26]:hover {\n  background-color: #8D6E63;\n  color: #F5E6D3;\n}\n.bg-beige[data-v-9df05c26] {\n  background-color: #F5E6D3;\n}\n.bg-light-beige[data-v-9df05c26] {\n  background-color: #D7CCC8;\n}\n.text-beige[data-v-9df05c26] {\n  color: #F5E6D3;\n}\n[data-v-9df05c26]:focus-visible {\n  outline: 2px solid #8D6E63 !important;\n  outline-offset: 2px;\n}\n", ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/laravel-mix/node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/laravel-mix/node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_style_index_0_id_9df05c26_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!../../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css */ "./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_style_index_0_id_9df05c26_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_style_index_0_id_9df05c26_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./resources/js/components/blog/Editar.vue":
/*!*************************************************!*\
  !*** ./resources/js/components/blog/Editar.vue ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Editar_vue_vue_type_template_id_9df05c26_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Editar.vue?vue&type=template&id=9df05c26&scoped=true */ "./resources/js/components/blog/Editar.vue?vue&type=template&id=9df05c26&scoped=true");
/* harmony import */ var _Editar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Editar.vue?vue&type=script&lang=js */ "./resources/js/components/blog/Editar.vue?vue&type=script&lang=js");
/* harmony import */ var _Editar_vue_vue_type_style_index_0_id_9df05c26_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css */ "./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css");
/* harmony import */ var C_wamp64_www_Panaderia_v2_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,C_wamp64_www_Panaderia_v2_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_Editar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Editar_vue_vue_type_template_id_9df05c26_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-9df05c26"],['__file',"resources/js/components/blog/Editar.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/components/blog/Editar.vue?vue&type=script&lang=js":
/*!*************************************************************************!*\
  !*** ./resources/js/components/blog/Editar.vue?vue&type=script&lang=js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Editar.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=script&lang=js");
 

/***/ }),

/***/ "./resources/js/components/blog/Editar.vue?vue&type=template&id=9df05c26&scoped=true":
/*!*******************************************************************************************!*\
  !*** ./resources/js/components/blog/Editar.vue?vue&type=template&id=9df05c26&scoped=true ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_template_id_9df05c26_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_template_id_9df05c26_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Editar.vue?vue&type=template&id=9df05c26&scoped=true */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=template&id=9df05c26&scoped=true");


/***/ }),

/***/ "./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css":
/*!*********************************************************************************************************!*\
  !*** ./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css ***!
  \*********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_laravel_mix_node_modules_style_loader_dist_cjs_js_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Editar_vue_vue_type_style_index_0_id_9df05c26_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!../../../../node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!../../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css */ "./node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/blog/Editar.vue?vue&type=style&index=0&id=9df05c26&scoped=true&lang=css");


/***/ })

}]);