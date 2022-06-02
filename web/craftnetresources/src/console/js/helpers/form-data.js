export default {

    append(formData, name, value) {
        if (value === null) {
            value = ''
        }

        formData.append(name, value)
    }

}