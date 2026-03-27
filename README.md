<p align="center">
    <img src="https://raw.githubusercontent.com/keeporsweep/keeporsweep.net/master/images/icon-256.png" height="128">
</p>
<h3 align="center"><a href="http://keeporsweep.net">Keep or Sweep</a></h3>
<p align="center">Randomly declutter your <a href="https://nextcloud.com">Nextcloud</a>!<p>
<p align="center">
    <img src="https://raw.githubusercontent.com/keeporsweep/keeporsweep.net/master/images/screenshot-nextcloud.png" height="500">
</p>



## Install

This is the [☁️Nextcloud app](https://apps.nextcloud.com/apps/keeporsweep). You can easily install it from inside your Nextcloud through the app management.

There’s also a [desktop app for ⊞Windows, 🍏macOS & 🐧Linux](https://github.com/keeporsweep/keeporsweep-desktop#keep-or-sweep), and more info at [🔀keeporsweep.net](http://keeporsweep.net).



## Contribute

Contributions are always welcome! 😍 Check out the [list of issues](https://github.com/keeporsweep/keeporsweep/issues) and see what you like to contribute. We use [Vue.js](https://vuejs.org/) as Javascript framework and [Animate.css](https://daneden.github.io/animate.css/) for the animations.


### Development setup

1. Clone this app into your `nextcloud/apps/` folder:
```
git clone https://github.com/keeporsweep/keeporsweep.git
```
2. Enable it from the apps management inside Nextcloud
3. Get hacking 🎉


## Maintenance strategy for future Nextcloud versions

No app can be guaranteed to work with all unknown future Nextcloud versions without any maintenance. But this repository can stay **low-maintenance** by combining stable API usage and automation:

1. **Use only public `OCP\\*` APIs** in app code (avoid internal `OC\\*` classes).
2. Keep compatibility claims in `appinfo/info.xml` aligned with tested ranges.
3. Run CI on every PR and weekly (`.github/workflows/ci.yml`) to catch regressions early.
4. Use a conservative release policy: bump supported Nextcloud max-version only after CI verification.

### Practical release checklist

- Run PHP lint + tests.
- Verify app metadata (`composer validate`, `appinfo/info.xml` values).
- Check for internal API usage regressions (`OC\\` namespace imports).
- Test app manually on at least one currently supported Nextcloud release before tagging.
