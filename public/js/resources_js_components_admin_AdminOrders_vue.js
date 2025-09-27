"use strict";
(self["webpackChunkpanaderia"] = self["webpackChunkpanaderia"] || []).push([["resources_js_components_admin_AdminOrders_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=script&lang=js":
/*!***********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'AdminOrders',
  setup() {
    const loading = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(true);
    const orders = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)([]);
    const meta = (0,vue__WEBPACK_IMPORTED_MODULE_1__.reactive)({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20
    });
    const filters = (0,vue__WEBPACK_IMPORTED_MODULE_1__.reactive)({
      status: new URLSearchParams(window.location.search).get('status') || ''
    });
    const completingIds = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(new Set());

    // Detalles
    const selected = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(null);
    const orderDetails = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(null);
    const detailsLoading = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(false);
    const showDetails = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(false);
    const formatDate = d => d ? new Date(d).toLocaleString() : '—';
    const paymentMethod = pm => {
      const m = String(pm || '').toLowerCase();
      if (m === 'cash') return 'Efectivo';
      if (m === 'transfer') return 'Transferencia';
      if (m === 'zelle') return 'Zelle';
      if (m === 'card') return 'Tarjeta';
      if (m === 'mobile') return 'Pago móvil';
      return '—';
    };
    const badgeClass = b => {
      return `badge ${b === 'success' ? 'bg-success' : b === 'warning' ? 'bg-warning text-dark' : b === 'danger' ? 'bg-danger' : b === 'info' ? 'bg-info text-dark' : 'bg-secondary'}`;
    };
    const load = async (page = 1) => {
      loading.value = true;
      try {
        const {
          data
        } = await axios__WEBPACK_IMPORTED_MODULE_0___default().get('/api/admin/orders', {
          params: {
            status: filters.status || undefined,
            page,
            perPage: meta.per_page
          }
        });
        orders.value = data?.data || [];
        meta.current_page = data?.meta?.current_page || page;
        meta.last_page = data?.meta?.last_page || 1;
        meta.total = data?.meta?.total || orders.value.length;
      } catch (e) {
        console.error('Error cargando pedidos:', e);
        orders.value = [];
      } finally {
        loading.value = false;
      }
    };
    const go = page => load(page);
    const openDetails = async o => {
      selected.value = o;
      orderDetails.value = null;
      detailsLoading.value = true;
      showDetails.value = true;
      try {
        const {
          data
        } = await axios__WEBPACK_IMPORTED_MODULE_0___default().get(`/api/orders/${o.id}`);
        orderDetails.value = data;
      } catch (e) {
        console.error('No se pudieron cargar los detalles:', e);
      } finally {
        detailsLoading.value = false;
      }
    };
    const hideModal = () => {
      showDetails.value = false;
    };

    // Permitir completar cash_on_delivery y pending
    const canComplete = o => {
      const s = String(o?.status || '').toLowerCase();
      return s === 'cash_on_delivery' || s === 'pending';
    };
    const completeOrder = async o => {
      if (!o?.id) return;
      completingIds.value.add(o.id);
      try {
        await axios__WEBPACK_IMPORTED_MODULE_0___default().post(`/api/orders/${o.id}/complete-cash`);
        await load(meta.current_page); // refresca lista actual
      } catch (e) {
        console.error('No se pudo completar la orden:', e);
        alert('No se pudo completar la orden. Intenta nuevamente.');
      } finally {
        completingIds.value.delete(o.id);
      }
    };
    (0,vue__WEBPACK_IMPORTED_MODULE_1__.onMounted)(() => load());
    return {
      loading,
      orders,
      meta,
      filters,
      formatDate,
      paymentMethod,
      badgeClass,
      go,
      load,
      selected,
      orderDetails,
      openDetails,
      detailsLoading,
      showDetails,
      hideModal,
      completingIds,
      canComplete,
      completeOrder
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

const _hoisted_1 = {
  class: "p-3"
};
const _hoisted_2 = {
  class: "d-flex justify-content-between align-items-center mb-3"
};
const _hoisted_3 = {
  class: "d-flex gap-2"
};
const _hoisted_4 = {
  class: "card border-0 shadow-sm"
};
const _hoisted_5 = {
  class: "card-body p-0"
};
const _hoisted_6 = {
  key: 0,
  class: "text-center py-4"
};
const _hoisted_7 = {
  key: 0,
  class: "p-4 text-center text-muted"
};
const _hoisted_8 = {
  key: 1,
  class: "table-responsive"
};
const _hoisted_9 = {
  class: "table align-middle mb-0"
};
const _hoisted_10 = {
  class: "text-brown fw-semibold"
};
const _hoisted_11 = {
  class: "d-flex gap-2"
};
const _hoisted_12 = ["onClick"];
const _hoisted_13 = ["disabled", "onClick"];
const _hoisted_14 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
const _hoisted_15 = {
  class: "d-flex justify-content-between align-items-center p-3 border-top"
};
const _hoisted_16 = {
  class: "text-muted"
};
const _hoisted_17 = {
  class: "btn-group"
};
const _hoisted_18 = ["disabled"];
const _hoisted_19 = ["disabled"];
const _hoisted_20 = {
  key: 0
};
const _hoisted_21 = {
  class: "modal fade show",
  style: {
    "display": "block"
  },
  tabindex: "-1",
  role: "dialog",
  "aria-modal": "true"
};
const _hoisted_22 = {
  class: "modal-dialog modal-lg modal-dialog-scrollable"
};
const _hoisted_23 = {
  class: "modal-content"
};
const _hoisted_24 = {
  class: "modal-header"
};
const _hoisted_25 = {
  class: "modal-title text-brown"
};
const _hoisted_26 = {
  class: "modal-body"
};
const _hoisted_27 = {
  key: 0,
  class: "text-center py-3"
};
const _hoisted_28 = {
  class: "mb-3"
};
const _hoisted_29 = {
  class: "table-responsive"
};
const _hoisted_30 = {
  class: "table"
};
const _hoisted_31 = {
  class: "d-flex justify-content-end"
};
const _hoisted_32 = {
  class: "mb-0"
};
const _hoisted_33 = {
  class: "text-brown"
};
const _hoisted_34 = {
  key: 2,
  class: "text-muted"
};
const _hoisted_35 = {
  class: "modal-footer"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [_cache[7] || (_cache[7] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
    class: "text-brown mb-0"
  }, "Todos los pedidos"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("small", {
    class: "text-muted"
  }, "Listado administrativo")], -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("select", {
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => $setup.filters.status = $event),
    class: "form-select"
  }, _cache[6] || (_cache[6] = [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createStaticVNode)("<option value=\"\" data-v-5c2e2779>Todos los estados</option><option value=\"pending\" data-v-5c2e2779>Pendiente</option><option value=\"cash_on_delivery\" data-v-5c2e2779>Contra entrega</option><option value=\"awaiting_payment\" data-v-5c2e2779>En espera de pago</option><option value=\"awaiting_review\" data-v-5c2e2779>En revisión</option><option value=\"processing\" data-v-5c2e2779>Procesando</option><option value=\"paid\" data-v-5c2e2779>Completado</option><option value=\"cancelled\" data-v-5c2e2779>Cancelado</option>", 8)]), 512 /* NEED_PATCH */), [[vue__WEBPACK_IMPORTED_MODULE_0__.vModelSelect, $setup.filters.status]]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    class: "btn btn-brown",
    onClick: _cache[1] || (_cache[1] = $event => $setup.load())
  }, "Aplicar")])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [$setup.loading ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, _cache[8] || (_cache[8] = [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    class: "spinner-border text-brown",
    role: "status"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
    class: "visually-hidden"
  }, "Cargando...")], -1 /* HOISTED */)]))) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    key: 1
  }, [$setup.orders.length === 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, " No hay pedidos para mostrar. ")) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_8, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("table", _hoisted_9, [_cache[10] || (_cache[10] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("thead", {
    class: "bg-brown text-white"
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("tr", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "110px"
    }
  }, "Pedido"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", null, "Cliente"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "130px"
    }
  }, "Total"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "150px"
    }
  }, "Pago"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "140px"
    }
  }, "Estado"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "170px"
    }
  }, "Fecha"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "200px"
    }
  }, "Acciones")])], -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("tbody", null, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.orders, o => {
    return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("tr", {
      key: o.id
    }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(o.code || 'ORD-' + String(o.id).padStart(4, '0')), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(o.customer_name || '—'), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, "$" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(Number(o.total || 0).toFixed(2)), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.paymentMethod(o.payment_method)), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
      class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)($setup.badgeClass(o.status_badge))
    }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(o.status_label), 3 /* TEXT, CLASS */)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.formatDate(o.created_at)), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", _hoisted_11, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
      class: "btn btn-sm btn-outline-brown",
      onClick: $event => $setup.openDetails(o)
    }, " Ver detalles ", 8 /* PROPS */, _hoisted_12), $setup.canComplete(o) ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
      key: 0,
      class: "btn btn-sm btn-brown",
      disabled: $setup.completingIds.has(o.id),
      onClick: $event => $setup.completeOrder(o)
    }, [$setup.completingIds.has(o.id) ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_14)) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), _cache[9] || (_cache[9] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Completar "))], 8 /* PROPS */, _hoisted_13)) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])]);
  }), 128 /* KEYED_FRAGMENT */))])])])), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Paginación "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_15, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("small", _hoisted_16, " Página " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.meta.current_page) + " de " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.meta.last_page) + " — " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.meta.total) + " pedidos ", 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_17, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    class: "btn btn-outline-brown btn-sm",
    disabled: $setup.meta.current_page <= 1,
    onClick: _cache[2] || (_cache[2] = $event => $setup.go($setup.meta.current_page - 1))
  }, " Anterior ", 8 /* PROPS */, _hoisted_18), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    class: "btn btn-outline-brown btn-sm",
    disabled: $setup.meta.current_page >= $setup.meta.last_page,
    onClick: _cache[3] || (_cache[3] = $event => $setup.go($setup.meta.current_page + 1))
  }, " Siguiente ", 8 /* PROPS */, _hoisted_19)])])], 64 /* STABLE_FRAGMENT */))])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Modal Detalles (Vue controlled) "), $setup.showDetails ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_20, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_22, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_23, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h5", _hoisted_25, " Detalle del pedido " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.selected?.code || 'ORD-' + String($setup.selected?.id || '').padStart(4, '0')), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    type: "button",
    class: "btn-close",
    onClick: _cache[4] || (_cache[4] = $event => $setup.hideModal())
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_26, [$setup.detailsLoading ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_27, _cache[11] || (_cache[11] = [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    class: "spinner-border text-brown"
  }, null, -1 /* HOISTED */)]))) : $setup.orderDetails ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    key: 1
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_28, [_cache[12] || (_cache[12] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("strong", null, "Cliente:", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.orderDetails.name), 1 /* TEXT */), _cache[13] || (_cache[13] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("br", null, null, -1 /* HOISTED */)), _cache[14] || (_cache[14] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("strong", null, "Teléfono:", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.orderDetails.phone), 1 /* TEXT */), _cache[15] || (_cache[15] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("br", null, null, -1 /* HOISTED */)), _cache[16] || (_cache[16] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("strong", null, "Dirección:", -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.orderDetails.shipping_address || '—'), 1 /* TEXT */)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_29, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("table", _hoisted_30, [_cache[17] || (_cache[17] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("thead", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("tr", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", null, "Producto"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "100px"
    }
  }, "Cant."), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "120px"
    }
  }, "Precio"), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("th", {
    style: {
      "width": "120px"
    }
  }, "Subtotal")])], -1 /* HOISTED */)), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("tbody", null, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.orderDetails.items, it => {
    return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("tr", {
      key: it.id
    }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(it.name), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(it.quantity), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, "$" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(Number(it.price || it.unit_price || 0).toFixed(2)), 1 /* TEXT */), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("td", null, "$" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)((Number(it.price || it.unit_price || 0) * Number(it.quantity || 0)).toFixed(2)), 1 /* TEXT */)]);
  }), 128 /* KEYED_FRAGMENT */))])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_31, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h5", _hoisted_32, [_cache[18] || (_cache[18] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Total: ")), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_33, "$" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(Number($setup.orderDetails.total || 0).toFixed(2)), 1 /* TEXT */)])])], 64 /* STABLE_FRAGMENT */)) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_34, "No se pudo cargar el detalle."))]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_35, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    class: "btn btn-outline-brown",
    onClick: _cache[5] || (_cache[5] = $event => $setup.hideModal())
  }, "Cerrar")])])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" backdrop "), _cache[19] || (_cache[19] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
    class: "modal-backdrop fade show"
  }, null, -1 /* HOISTED */))])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)]);
}

/***/ }),

/***/ "./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, "\n.text-brown[data-v-5c2e2779] { color: #8B4513;\n}\n.bg-brown[data-v-5c2e2779] { background-color: #8B4513;\n}\n.btn-brown[data-v-5c2e2779] { background-color: #8B4513; color: #FFF8E7;\n}\n.btn-brown[data-v-5c2e2779]:hover { filter: brightness(0.95);\n}\n.btn-outline-brown[data-v-5c2e2779] { color: #8B4513; border-color: #8B4513;\n}\n.btn-outline-brown[data-v-5c2e2779]:hover { background-color: #8B4513; color: #FFF8E7;\n}\r\n", ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/laravel-mix/node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/laravel-mix/node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_style_index_0_id_5c2e2779_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!../../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css */ "./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_laravel_mix_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_style_index_0_id_5c2e2779_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_style_index_0_id_5c2e2779_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./resources/js/components/admin/AdminOrders.vue":
/*!*******************************************************!*\
  !*** ./resources/js/components/admin/AdminOrders.vue ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AdminOrders_vue_vue_type_template_id_5c2e2779_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true */ "./resources/js/components/admin/AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true");
/* harmony import */ var _AdminOrders_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AdminOrders.vue?vue&type=script&lang=js */ "./resources/js/components/admin/AdminOrders.vue?vue&type=script&lang=js");
/* harmony import */ var _AdminOrders_vue_vue_type_style_index_0_id_5c2e2779_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css */ "./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css");
/* harmony import */ var C_wamp64_www_ProyectoPanaderia_IA_Panaderia_v2_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,C_wamp64_www_ProyectoPanaderia_IA_Panaderia_v2_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_AdminOrders_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_AdminOrders_vue_vue_type_template_id_5c2e2779_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-5c2e2779"],['__file',"resources/js/components/admin/AdminOrders.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/components/admin/AdminOrders.vue?vue&type=script&lang=js":
/*!*******************************************************************************!*\
  !*** ./resources/js/components/admin/AdminOrders.vue?vue&type=script&lang=js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./AdminOrders.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=script&lang=js");
 

/***/ }),

/***/ "./resources/js/components/admin/AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true":
/*!*************************************************************************************************!*\
  !*** ./resources/js/components/admin/AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_template_id_5c2e2779_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_template_id_5c2e2779_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=template&id=5c2e2779&scoped=true");


/***/ }),

/***/ "./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css":
/*!***************************************************************************************************************!*\
  !*** ./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_laravel_mix_node_modules_style_loader_dist_cjs_js_node_modules_laravel_mix_node_modules_css_loader_dist_cjs_js_clonedRuleSet_9_use_1_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_9_use_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AdminOrders_vue_vue_type_style_index_0_id_5c2e2779_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!../../../../node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!../../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css */ "./node_modules/laravel-mix/node_modules/style-loader/dist/cjs.js!./node_modules/laravel-mix/node_modules/css-loader/dist/cjs.js??clonedRuleSet-9.use[1]!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-9.use[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/components/admin/AdminOrders.vue?vue&type=style&index=0&id=5c2e2779&scoped=true&lang=css");


/***/ })

}]);