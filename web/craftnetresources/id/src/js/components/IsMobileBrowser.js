import MobileDetect from 'mobile-detect';

export default {
  methods: {
    isMobileBrowser(detectTablets) {
      let agent = navigator.userAgent || navigator.vendor || window.opera;
      let md = new MobileDetect(agent);

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
