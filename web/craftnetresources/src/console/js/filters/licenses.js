export function formatCmsLicense(value) {
    const normalizedValue = value.replace(/(\r\n|\n|\r)/gm, "");

    const parts = [];

    if (normalizedValue.length > 0) {
        parts.push(normalizedValue.substr(0, 50));
    }

    if (normalizedValue.length > 50) {
        parts.push(normalizedValue.substr(50, 50));
    }

    if (normalizedValue.length > 100) {
        parts.push(normalizedValue.substr(100, 50));
    }

    if (normalizedValue.length > 150) {
        parts.push(normalizedValue.substr(150, 50));
    }

    if (normalizedValue.length > 200) {
        parts.push(normalizedValue.substr(200, 50));
    }

    if (normalizedValue.length > 250) {
        parts.push(normalizedValue.substr(250));
    }

    let formattedValue = '';

    for (let i = 0; i < parts.length; i++) {
        formattedValue += parts[i];

        if (i < parts.length - 1) {
            formattedValue += '\r\n';
        }
    }

    return formattedValue;
}

export function formatPluginLicense(value, format) {
    switch(format) {
        case 'short': {
            return value.substr(0, 4)
        }
        case 'full': {

            const parts = [];

            parts.push(value.substr(0, 4));
            parts.push(value.substr(4, 4));
            parts.push(value.substr(8, 4));
            parts.push(value.substr(12, 4));
            parts.push(value.substr(16, 4));
            parts.push(value.substr(20));

            let formattedValue = '';

            for (let i = 0; i < parts.length; i++) {
                formattedValue += parts[i];

                if (i < parts.length - 1) {
                    formattedValue += '-';
                }
            }

            return formattedValue;
        }
        default: {
            return `${value.substr(0, 4)}-****-****-****-****-****`
        }
    }
}