<?php

namespace LightnCandy;

class Flags
{
    // Handlebars.js compatibility
    const FLAG_STRICT = 524288;
    const FLAG_PREVENTINDENT = 131072;
    const FLAG_PARTIALNEWCONTEXT = 536870912;
    const FLAG_IGNORESTANDALONE = 1073741824;

    // PHP behavior flags
    const FLAG_PROPERTY = 32768;
    const FLAG_RUNTIMEPARTIAL = 1048576;
    const FLAG_NOESCAPE = 67108864;
}
