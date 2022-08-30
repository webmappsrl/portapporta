
<template>
    <div id="container">
        <div :id="mapRef" class="wm-map"></div>
    </div>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import "leaflet/dist/leaflet.css";
import L from "leaflet";
const DEFAULT_TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const DEFAULT_ATTRIBUTION = '<a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery (c) <a href="https://www.mapbox.com/">Mapbox</a>';
const DEFAULT_CENTER = [0, 0];
console.log('area');
export default {
    name: "MapPolygon",
    mixins: [FormField, HandlesValidationErrors],
    props: ['field', 'edit'],
    data() { 
        return { 
            mapRef: `mapContainer-${Math.floor(Math.random() * 10000 + 10)}` ,
            uploadFileContainer : 'uploadFileContainer'
        } 
    },
    methods: {
        initMap() {
            setTimeout(() => {
                const center = this.field.center ?? this.center ?? DEFAULT_CENTER;
                const area = this.field.area;
                const mapDiv = L.map(this.mapRef).setView(center, 13);
                const myZoom = {
                    start: mapDiv.getZoom(),
                    end: mapDiv.getZoom()
                };

                L.tileLayer(
                    this.field.tiles ?? DEFAULT_TILES,
                    {
                        attribution: this.field.attribution ?? DEFAULT_ATTRIBUTION,
                        maxZoom: 15,
                        minZoom: 11,
                        id: "mapbox/streets-v11",
                    }
                ).addTo(mapDiv);
                var polygonOption = {
                    fillColor: '#f03',
                    fillOpacity: 0.5,
                };
                var polygon = L.polygon(area,polygonOption).addTo(mapDiv);
                mapDiv.fitBounds(polygon.getBounds());
                if (this.edit) {
                    mapDiv.on('zoomstart', function () {
                        myZoom.start = mapDiv.getZoom();
                    });
                    mapDiv.on('zoomend', function () {
                        myZoom.end = mapDiv.getZoom();
                        var diff = myZoom.start - myZoom.end;
                        if (diff > 0) {
                            polygon.setRadius(polygon.getRadius() * 2);
                        } else if (diff < 0) {
                            polygon.setRadius(polygon.getRadius() / 2);
                        }
                    });
                } else {
                    mapDiv.dragging.disable();
                    mapDiv.zoomControl.remove()
                    mapDiv.scrollWheelZoom.disable();
                    mapDiv.doubleClickZoom.disable();
                }
            }, 300);

        }
    },
    mounted() {
        this.initMap();
    },
};
</script>
