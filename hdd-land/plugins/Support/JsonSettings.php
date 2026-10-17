<?php

namespace Plugins\Support;

use App\Support\JsonSettings as AppJsonSettings;

/**
 * Compatibility alias — some plugins import Plugins\Support\JsonSettings
 * while the shared helper lives under App\Support.
 */
class JsonSettings extends AppJsonSettings
{
}
