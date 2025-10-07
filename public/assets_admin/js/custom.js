const priceFormat = wNumb({
    thousand: '.',
    decimals: 2,
    mark: ','
})

Inputmask({
    mask: '99:99',
    placeholder: '__:__',
}).mask('.time-mask');

Inputmask({
    mask: '99-99-9999',
    placeholder: '__-__-____',
}).mask('.date-mask');

function propSubmitButton(btn,status = 1){
    if(status == 1){
        btn.attr("data-kt-indicator", "on");
        btn.prop("disabled", true);
    }else{
        btn.attr("data-kt-indicator", "off");
        btn.prop("disabled", false);
    }
}
function resetForm(form){
    form.find("input:not([name='_token']):not([type='checkbox'])").val("");
    form.find("select").val("");
    form.find("textarea").val("");
    form[0].reset();
    form.find("select").trigger("change");
}
function generateRandomPassword(length = 8) {
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
        password = '',
        hasLetter = false,
        hasDigit = false;

    for (var i = 0; i < length; i++) {
        var randomIndex = Math.floor(Math.random() * characters.length);
        var randomChar = characters.charAt(randomIndex);

        if (/[A-Za-z]/.test(randomChar)) {
            hasLetter = true;
        }
        else if (/\d/.test(randomChar)) {
            hasDigit = true;
        }

        password += randomChar;
    }

    if (!hasLetter || !hasDigit) {
        return generateRandomPassword(length);
    }

    return password;
}

const priceInput = $(".price-input");
priceInput.attr("placeholder", "0,00");

$(document).on('blur', '.price-input', function (){
    if($(this).val() && (/\d/.test($(this).val()))){
        $(this).val(priceFormat.to(priceFormat.from($(this).val())))
    }else{
        $(this).val("")
    }
})

priceInput.each(function () {
    $(this).trigger("blur");
});

$(".dateInput").flatpickr({
    dateFormat: 'd-m-Y',
    enableTime: false,
    locale: {
        weekdays: {
            longhand: ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
            shorthand: ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt']
        },
        months: {
            longhand: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
            shorthand: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara']
        },
        today: 'Bugün',
        clear: 'Temizle',
        firstDayOfWeek: 1,
        time_24hr: true
    },
});

$(".dateTimeInput").flatpickr({
    dateFormat: 'd-m-Y H:i',
    enableTime: true,
    locale: {
        weekdays: {
            longhand: ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
            shorthand: ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt']
        },
        months: {
            longhand: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
            shorthand: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara']
        },
        today: 'Bugün',
        clear: 'Temizle',
        firstDayOfWeek: 1,
        time_24hr: true
    },
});

function formatDecimalInput(value) {
    if (value === null) {
        return null;
    }

    return value.replace(/\./g, '').replace(',', '.');
}

function formatToHourMinute(seconds = null, limit = null) {
    if(seconds === null){
        return '0 dakika';
    }

    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    const parts = [];

    if (hours > 0) {
        parts.push(`${hours} saat`);
    }

    if (remainingMinutes > 0) {
        parts.push(`${remainingMinutes} dakika`);
    }

    if (parts.length === 0) {
        return '0 dakika';
    }

    if (limit !== null) {
        return parts.slice(0, limit).join(' ');
    }

    return parts.join(' ');
}

function formatNumber(number = null, format = 'short') {
    if(!number){
        return '0';
    }
    if (format === 'short') {
        if (number >= 1000000000) {
            return (number / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
        }
        if (number >= 1000000) {
            return (number / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (number >= 1000) {
            return (number / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return number.toString();
    }

    if (format === 'dot') {
        return number.toLocaleString('tr-TR');
    }

    return number.toString();
}

function limitText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function formatCurrency(value, locale = 'tr-TR', currency = 'TRY') {
    if (isNaN(value)) return '₺0,00';

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
}