<script src="{{ Module::asset('dashboard:vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script>
    $fileCount = $('.jsFileCount');
</script>
<style>
    .btn-upload {
        margin-bottom: 20px;
    }
    .jsThumbnailImageWrapper figure {
        position: relative;
        display: inline-block;
        margin-right: 20px;
        margin-bottom: 20px;
        background-color: #fff;
        border: 1px solid #eee;
        padding: 3px;
        border-radius: 3px;
        cursor: grab;
    }
    .jsThumbnailImageWrapper i.removeIcon {
        position: absolute;
        top:-10px;
        right:-10px;
        color: #f56954;
        font-size: 2em;
        background: white;
        border-radius: 20px;
        height: 25px;
    }

    figure.ui-state-highlight {
        border: none;
        width:100px;
        height: 0;
    }
    .gallery-wrap {
        overflow: auto;
    }
</style>
<?php
$random = mt_rand();
?>
<script>
    window["openMediaWindow{{ $random }}"] = function (event) {
            window.open('{!! route('media.grid.select') !!}?callbackFunction=includeMedia{{ $random }}&multiple=1&accept={{ $accept }}', '_blank', 'menubar=no,status=no,toolbar=no,scrollbars=yes,height=500,width=1000');
    };
    window["includeMedia{{ $random }}"] = function (mediaId, mediaUrl) {
        var accept = /{{ empty($accept) ? '.' : $accept }}/;
        if (!accept.test(mediaUrl)) {
            alert("{{ trans('media::message.Invalid file type') }}");
            return;
        }
        $.ajax({
            type: 'POST',
            url: '{{ route('api.media.link') }}',
            data: {
                'mediaId': mediaId,
                '_token': '{{ csrf_token() }}',
                'entityClass': '{{ $entityClass }}',
                'entityId': '{{ $entityId }}',
                'zone': '{{ $zone }}',
                'order': $('.gallery-wrap-{{ $random }} .jsThumbnailImageWrapper figure').size() + 1
            },
            success: function (data) {
                if(!data.error) {
                    var html = '<figure data-id="' + data.result.imageableId + '"><img src="' + data.result.path + '" alt=""/>' +
                            '<a class="jsRemoveLink" href="#" data-id="' + data.result.imageableId + '">' +
                            '<i class="fa fa-times-circle removeIcon"></i>' +
                            '</a></figure>';
                    $('.gallery-wrap-{{ $random }} .jsThumbnailImageWrapper').append(html).fadeIn();
                    if ($fileCount.length > 0) {
                        var count = parseInt($fileCount.text());
                        $fileCount.text(count + 1);
                    }
                }
            }
        });
    };
</script>
<div class="form-group gallery-wrap-{{ $random }}">
    {!! Form::label($zone, ucwords(str_replace('_', ' ', $zone)) . ':') !!}
    <div class="clearfix"></div>
    <?php $url = route('media.grid.select') ?>
    <a class="btn btn-primary btn-upload" onclick="openMediaWindow{{ $random }}(event, '{{ $zone }}')"><i class="fa fa-upload"></i>
        {{ trans('media::media.Browse') }}
    </a>
    <div class="clearfix"></div>
    <div class="jsThumbnailImageWrapper">
        <?php $zoneVar = "{$zone}Files"  ?>
        <?php if (isset($$zoneVar)): ?>
            <?php foreach ($$zoneVar as $file): ?>
                <figure data-id="{{ $file->pivot->id }}">
                    <img src="{{ Imagy::getThumbnail($file->path, (isset($thumbnailSize) ? $thumbnailSize : 'mediumThumb')) }}" alt="{{ $file->alt_attribute }}"/>
                    <a class="jsRemoveLink" href="#" data-id="{{ $file->pivot->id }}">
                        <i class="fa fa-times-circle removeIcon"></i>
                    </a>
                </figure>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script>
    $( document ).ready(function() {
        $('.gallery-wrap-{{ $random }} .jsThumbnailImageWrapper').on('click', '.jsRemoveLink', function (e) {
            e.preventDefault();
            var imageableId = $(this).data('id'),
                    pictureWrapper = $(this).parent();
            $.ajax({
                type: 'POST',
                url: '{{ route('api.media.unlink') }}',
                data: {
                    'imageableId': imageableId,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.error === false) {
                        pictureWrapper.fadeOut().remove();
                        if ($fileCount.length > 0) {
                            var count = parseInt($fileCount.text());
                            $fileCount.text(count - 1);
                        }
                    } else {
                        pictureWrapper.append(data.message);
                    }
                }
            });
        });

        $(".gallery-wrap-{{ $random }} .jsThumbnailImageWrapper").sortable({
            placeholder: 'ui-state-highlight',
            cursor:'move',
            helper: 'clone',
            containment: 'parent',
            forcePlaceholderSize: false,
            forceHelperSize: true,
            update:function(event, ui) {
                var dataSortable = $(this).sortable('toArray', {attribute: 'data-id'});
                $.ajax({
                    global: false, /* leave it to false */
                    type: 'POST',
                    url: '{{ route('api.media.sort') }}',
                    data: {
                        'entityClass': '{{ $entityClass }}',
                        'zone': '{{ $zone }}',
                        'sortable': dataSortable,
                        '_token': '{{ csrf_token() }}'
                    }
                });
            }
        });
    });
</script>
