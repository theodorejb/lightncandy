<?php

namespace LightnCandy;

class Flags
{
    // Compile time error handling flags
    const FLAG_ERROR_EXCEPTION = 2;

    // Handlebars.js compatibility
    const FLAG_PREVENTINDENT = 131072;
    const FLAG_PARTIALNEWCONTEXT = 536870912;
    const FLAG_IGNORESTANDALONE = 1073741824;

    // PHP behavior flags
    const FLAG_PROPERTY = 32768;
    const FLAG_RUNTIMEPARTIAL = 1048576;
    const FLAG_NOESCAPE = 67108864;

    // Template rendering time debug flags
    const FLAG_RENDER_DEBUG = 524288;
}
