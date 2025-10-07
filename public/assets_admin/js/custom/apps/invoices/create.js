"use strict";
var KTAppInvoicesCreate = function () {
    var e, priceWithVat, totalVat, t = function () {
        totalVat = 0;
        priceWithVat = 0;
        var t = [].slice.call(e.querySelectorAll('[data-kt-element="items"] [data-kt-element="item"]')), a = 0,
            n = priceFormat
        t.map((function (e) {
            var t = e.querySelector('[data-kt-element="quantity"]'), l = e.querySelector('[data-kt-element="price"]'),
                vatPercent = $(e).find('[data-kt-element="vat_percent"]').val(),
                r = n.from(l.value);
            r = !r || r < 0 ? 0 : r;
            var i = parseInt(t.value);
            i = !i || i < 0 ? 1 : i
            l.value = n.to(r) === '0,00' ? null : n.to(r)
            t.value = i
            priceWithVat = ((r * parseFloat(vatPercent) / 100)) + r
            e.querySelector('[data-kt-element="total"]').innerText = n.to(priceWithVat * i)
            a += priceWithVat * i
            // console.log(r, "rpice")
            // console.log(i, "i > miktar")
            // console.log(a, "a > grand total")
            // console.log(l, "l > satır price")
            // console.log(t, "t > total price")
            let vat =  (r * i) * parseFloat(vatPercent) / 100;
            totalVat += vat;

        })), e.querySelector('[data-kt-element="sub-total"]').innerText = n.to(a - totalVat), e.querySelector('[data-kt-element="grand-total"]').innerText = n.to(a), e.querySelector('[data-kt-element="vat-total"]').innerText = n.to(totalVat)
    }, a = function () {
        if (0 === e.querySelectorAll('[data-kt-element="items"] [data-kt-element="item"]').length) {
            var t = e.querySelector('[data-kt-element="empty-template"] tr').cloneNode(!0);
            e.querySelector('[data-kt-element="items"] tbody').appendChild(t)
        } else KTUtil.remove(e.querySelector('[data-kt-element="items"] [data-kt-element="empty"]'))
    };
    return {
        init: function (n) {
            (e = document.querySelector("#invoiceForm")).querySelector('[data-kt-element="items"] [data-kt-element="add-item"]').addEventListener("click", (function (n) {
                n.preventDefault();
                var l = e.querySelector('[data-kt-element="item-template"] tr').cloneNode(!0);
                e.querySelector('[data-kt-element="items"] tbody').appendChild(l), a(), t()
            }));
            KTUtil.on(e, '[data-kt-element="items"] [data-kt-element="remove-item"]', "click", (function (e) {
                e.preventDefault(), KTUtil.remove(this.closest('[data-kt-element="item"]')), a(), t()
            }));
            $(e).on("change", '[data-kt-element="items"] [data-kt-element="quantity"], [data-kt-element="items"] [data-kt-element="price"], [data-kt-element="items"] [data-kt-element="vat_percent"]', function (event) {
                event.preventDefault();
                t();
            });
            t();
        }
    }
}();
KTUtil.onDOMContentLoaded((function () {
    KTAppInvoicesCreate.init()
}));


