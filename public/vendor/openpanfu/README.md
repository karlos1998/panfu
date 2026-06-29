# FlashClient

Place the original Panfu Flash client files here if you have them.

The CMS expects the main client at:

```text
FlashClient/Panfu.swf
```

The local setup currently also expects the runtime shared library at:

```text
FlashClient/rsl/library.swf
```

Many runtime assets have been mirrored into this directory from the current
public flash client. Any paths that were referenced by the client config but
still returned 404 are listed in:

```text
FlashClient/MISSING_ASSETS.txt
```

The registration loader can be built from `PanfuRegister/src` and served from:

```text
FlashClient/register/PanfuRegister.swf
```

That registration loader still needs `Reg_process_interface_CS3.swf` next to it at
runtime. OpenPanfu ships that asset as a `.fla`, so it has to be published with
Adobe Flash Professional/Animate or replaced with an already-published SWF.

These files are not distributed by OpenPanfu.
