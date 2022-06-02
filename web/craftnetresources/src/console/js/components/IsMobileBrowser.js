import MobileDetect from 'mobile-detect';

export default {
    methods: {
        isMobileBrowser(detectTablets) {
            const agent = navigator.userAgent || navigator.vendor || window.opera;
            const md = new MobileDetect(agent);

            if (detectTablets) {
                if (md.mobile()) {
                    return true;
                }
            } else {
                if (md.phone()) {
                    return true;
                }
            }

            return false;
        },
    }
}
