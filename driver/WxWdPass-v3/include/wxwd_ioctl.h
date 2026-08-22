/**
 * WxWdPass v3 — IOCTL contract (HamGap / Windex WD)
 *
 * Backward-compatible names with legacy WdHd 2.x where possible.
 * New AHCI/SATA paths use WXWD_IOCTL_* prefix.
 *
 * Build: include from user-mode service (Windex WD) and kernel driver.
 */
#pragma once

#include <windows.h>

#define WXWD_DEVICE_TYPE          0x8000
#define WXWD_IOCTL_BASE           0x900

/* Legacy-compatible IOCTL IDs (WdHd 2.x family) */
#define IOCTL_WXWD_INITIALIZE          CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x01, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_CLEANUP               CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x02, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_SCAN_DRIVES           CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x03, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_GET_ADAPTER_INFO      CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x04, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_GET_SETTINGS          CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x05, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_SET_SETTINGS          CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x06, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_GET_VERSION           CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x07, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_ATA_COMMAND           CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x10, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_PROTOCOL_COMMAND      CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x11, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_READ_TASKFILE         CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x12, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_WRITE_TASKFILE        CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x13, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_READ_PCICONFIG        CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x14, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_WRITE_PCICONFIG       CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x15, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_SATA_COMMAND          CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x20, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_READ_SATA_REGISTERS   CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x21, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_WRITE_SATA_REGISTERS  CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x22, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_INTERNAL_COMMAND      CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x30, METHOD_BUFFERED, FILE_ANY_ACCESS)

/* v3-only */
#define IOCTL_WXWD_ENUM_CONTROLLERS      CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x40, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_LOCK_PORT             CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x41, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_UNLOCK_PORT           CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x42, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_PORT_STATUS           CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x43, METHOD_BUFFERED, FILE_ANY_ACCESS)
#define IOCTL_WXWD_VERIFY_LICENSE        CTL_CODE(WXWD_DEVICE_TYPE, WXWD_IOCTL_BASE + 0x44, METHOD_BUFFERED, FILE_ANY_ACCESS)

#define WXWD_VERSION_MAJOR  3
#define WXWD_VERSION_MINOR  0
#define WXWD_VERSION_BUILD  0

#define WXWD_MAKE_VERSION(maj, min, bld)  (((maj) << 16) | ((min) << 8) | (bld))

typedef enum _WXWD_TRANSPORT {
  WxWdTransportUnknown = 0,
  WxWdTransportLegacyIde = 1,   /* PIO BasePort/AltPort (WdHdGen era) */
  WxWdTransportAhci = 2,        /* AHCI HBA port + FIS */
  WxWdTransportScsiPt = 3       /* SCSI pass-through fallback (no port replace) */
} WXWD_TRANSPORT;

typedef enum _WXWD_PORT_STATE {
  WxWdPortFree = 0,
  WxWdPortLocked = 1,
  WxWdPortBusy = 2,
  WxWdPortBootProtected = 3,
  WxWdPortOffline = 4
} WXWD_PORT_STATE;

typedef enum _WXWD_STATUS {
  WxWdOk = 0,
  WxWdErrDriverBusy = 1,
  WxWdErrPortBusy = 2,
  WxWdErrBootChannel = 3,
  WxWdErrUnsupported = 4,
  WxWdErrTimeout = 5,
  WxWdErrLicense = 6,
  WxWdErrNoDevice = 7,
  WxWdErrInvalidParam = 8
} WXWD_STATUS;

#pragma pack(push, 1)

typedef struct _WXWD_VERSION_INFO {
  ULONG DriverVersion;      /* WXWD_MAKE_VERSION */
  ULONG ApiVersion;         /* IOCTL contract version */
  ULONG LegacyCompat;       /* 1 = IOCTL_WDHDD_* shim enabled */
  WCHAR BuildLabel[32];     /* L"3.0.0-beta" */
} WXWD_VERSION_INFO;

typedef struct _WXWD_CONTROLLER_INFO {
  ULONG Index;
  ULONG PciVendorId;
  ULONG PciDeviceId;
  ULONG PciBus;
  ULONG PciDevice;
  ULONG PciFunction;
  WXWD_TRANSPORT Transport;
  ULONG PortCount;
  WCHAR Description[64];    /* e.g. Intel 300 Series AHCI */
  BOOLEAN BootAttached;     /* TRUE = install blocked */
} WXWD_CONTROLLER_INFO;

typedef struct _WXWD_DRIVE_INFO {
  ULONG ControllerIndex;
  ULONG PortIndex;          /* AHCI port 0..N-1 */
  ULONG DeviceId;           /* legacy-compatible slot id */
  BOOLEAN Present;
  BOOLEAN Locked;
  CHAR Model[40 + 1];
  CHAR Serial[20 + 1];
  CHAR Firmware[8 + 1];
  ULONG CapacitySectors;
  USHORT IdentifyWord93;    /* SATA signature hint */
} WXWD_DRIVE_INFO;

typedef struct _WXWD_LOCK_REQUEST {
  ULONG ControllerIndex;
  ULONG PortIndex;
  ULONG SessionId;          /* app session token */
  ULONG Flags;              /* bit0 = hide from Windows */
} WXWD_LOCK_REQUEST;

typedef struct _WXWD_LOCK_RESPONSE {
  WXWD_STATUS Status;
  ULONG DeviceId;
  WXWD_PORT_STATE PortState;
  WCHAR SymbolicLink[64];   /* \\.\WxWdPort0 */
} WXWD_LOCK_RESPONSE;

typedef struct _WXWD_ATA_COMMAND {
  ULONG DeviceId;
  UCHAR Feature;
  UCHAR SectorCount;
  UCHAR LbaLow;
  UCHAR LbaMid;
  UCHAR LbaHigh;
  UCHAR Device;
  UCHAR Command;
  ULONG BufferLength;
  /* variable data follows in same IOCTL buffer */
} WXWD_ATA_COMMAND;

typedef struct _WXWD_SATA_COMMAND {
  ULONG DeviceId;
  ULONG FisType;            /* Register H2D, etc. */
  UCHAR Fis[20];
  ULONG BufferLength;
  ULONG ProtocolFlags;      /* DMA/PIO, NCQ tag */
} WXWD_SATA_COMMAND;

#pragma pack(pop)

/* User-mode device interface GUID — register in driver INF */
/* {A7B3C4D5-E6F7-4890-ABCD-EF1234567890} */
#define WXWD_INTERFACE_GUID_STR L"{A7B3C4D5-E6F7-4890-ABCD-EF1234567890}"
