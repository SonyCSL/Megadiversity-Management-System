# Entry scheme

```
\artichoke\framework\abstract\MongodbBase
 │
 ├ Entry
 │  └ File
 │     ├ Image
 │     ├ Json (WIP)
 │     ├ Csv  (WIP)
 │     ├ Gexf (WIP)
 │
 └ Index

 ─ Data
 ─ Metadata
```

## Basic information

- "Entry" contains two types of models.
  - "Files" is the model of the text or binary styled standard files with some string data and metadata, like jpeg, json, csv and a lots. This is stored on the MongoDB collections "fs.files" and "fs.chunks" controlled by MongoDB GridFS.
  - "Datastrings" is the model of only tiny strings styled by simple Key-Value data document like json. This is stored on "dataStrings" MongoDB collection as json format. This model is assumed using by high-frequentry (and low-volumed) data collection with tiny sensor (IoT) device joined on narrow band network like MQTT, WebSocket, Zigbee, BLE, SIGFOX, LoRaWAN.

- "Data" and "Metadata" class have no any dependency. Use for only handling MMS specific data structure.

### BSON (at MongoDB) structure

## Usage

### Create

(draft)

- All strings of user specific data (core body of Datastring entries, File entries will be able to contain similary) are processed by **Data** class.
- Only at File entries, embedded data like EXIF are processed by **Metadata** class.

|Entry type|Data::__construct()|Metadata::__costruct()|
|:--|:--|:--|
|Datastring with no body| - | - |
|Standard datastring|Requests->get('data')| - |
|File with no datastrings| - |Requests->filepath('file')|
|File with datastrings|Requests->get('data')|Requests->filepath('file')|

- Both of classes have `toArray()` method and generates array for storing to MongoDB collection.

### Read

1. Find or Listup entries on an album using by class **Index**.
2. Get entry ID by above process.
3. Create an instance of **Entry** class by entry ID and get data by the methods.

### Update

### Delete


## class: Entry

Use by "dataStrings" entry. This class contains common methods on entire entries.

## class: File

Use by "Files" entry. This class contains common methods on entire **Files** entries. If would like to get file specific data (thumbnail, data average, etc), use the other class that extends File class.