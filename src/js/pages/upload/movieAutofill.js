globalapp.uploadMovieAutofill = function uploadMovieAutofill() {
  function setLoading(loading) {
    if (loading) {
      $('.Button.autofill').addClass('is-loading').prop('disabled', true)
    } else {
      $('.Button.autofill').removeClass('is-loading').prop('disabled', false)
    }
  }

  var imdb = $('#imdb').val().match(/tt\d+/)
  if (imdb) {
    imdb = imdb[0]
  } else {
    return
  }

  setLoading(true)
  $.ajax({
    url: 'upload.php',
    data: {
      action: 'movie_info',
      imdbid: imdb,
    },
    type: 'GET',
    error: (err) => {
      setLoading(false)
      globalapp.setFormError('common.imdb_unknown_error')
    },
    success: (data) => {
      setLoading(false)
      globalapp.setFormError(null)
      if (data.code) {
        globalapp.setFormError(
          data.code === 1
            ? 'error.invalid_imdb_link_note'
            : data.code === 2
            ? 'error.torrent_group_exists_note'
            : 'error.imdb_unknown_error',
          data.code === 2 && { groupID: data.error.GroupID }
        )
        return
      }
      data = data.response
      if (data.Title) {
        $('#name').val(data.Title)
      }
      if (data.SubTitle) {
        $('#subname').val(data.SubTitle)
      }
      if (data.Poster) {
        $('#image').val(data.Poster)
      }
      if (data.Plot) {
        $('#desc').val(data.Plot)
      }
      if (data.Production) {
        $('#remaster_record_label').val(data.Production.replace(/, ?/, ' / '))
      }
      if (data.Year) {
        $('#year').val(data.Year)
      }
      if (data.Genre) {
        $('#tags').val(data.Genre.toLowerCase().replace('-', '.'))
      }
      if (data.Type == 'Movie') {
        $('#releasetype').val(1)
      }
      var artists = [],
        importance = [],
        artist_ids = []
      if (data.Directors) {
        Object.keys(data.Directors).map((k) => {
          artists.push(data.Directors[k])
          artist_ids.push(k)
          importance.push(1)
        })
      }
      if (data.Writers) {
        Object.keys(data.Writers).map((k) => {
          artists.push(data.Writers[k])
          artist_ids.push(k)
          importance.push(2)
        })
      }
      if (data.Producers) {
        Object.keys(data.Producers).map((k) => {
          artists.push(data.Producers[k])
          artist_ids.push(k)
          importance.push(3)
        })
      }
      if (data.Composers) {
        Object.keys(data.Composers).map((k) => {
          artists.push(data.Composers[k])
          artist_ids.push(k)
          importance.push(4)
        })
      }
      if (data.Cinematographers) {
        Object.keys(data.Cinematographers).map((k) => {
          artists.push(data.Cinematographers[k])
          artist_ids.push(k)
          importance.push(5)
        })
      }
      if (data.Casts) {
        Object.keys(data.Casts).map((k) => {
          artists.push(data.Casts[k])
          artist_ids.push(k)
          importance.push(6)
        })
      }
      if (data.RestCasts) {
        Object.keys(data.RestCasts).map((k) => {
          artists.push(data.RestCasts[k])
          artist_ids.push(k)
          importance.push(6)
        })
      }
      globalapp.uploadRemoveAllArtistFields()
      for (var i = 0; i < artists.length; i++) {
        var artistid, importanceid, artistimdbid, artist_cname
        if (i) {
          artistid = '#artist_' + i
          importanceid = '#importance_' + i
          artistimdbid = '#artist_id_' + i
          artist_cname = '#artist_chinese_' + i
          globalapp.uploadAddArtistField(true)
        } else {
          artistid = '#artist'
          importanceid = '#importance'
          artistimdbid = '#artist_id'
          artist_cname = '#artist_chinese'
        }
        $(artistid).val(artists[i])
        $(importanceid).val(importance[i])
        $(artistimdbid).val(artist_ids[i])
        if (data.ChineseName) {
          $(artist_cname).val(data.ChineseName[[artists[i]]])
        }
      }
      $('.FormValidation')[0].validator.validate()
      $('.FormUpload').addClass('u-formUploadAutoFilled')
      $(
        '.u-formUploadArtistList input:not([name="artists_chinese[]"]), .u-formUploadArtistList select'
      ).prop('disabled', true)
      if (artists.length >= 5) {
        globalapp.uploadArtistsShowMore({ hide: true })
      }
    },
    dataType: 'json',
  })
}

global.setFormError = function setFormError(key, options = {}) {
  if (key) {
    const message = lang.get(key, options)
    $('.imdb.Form-errorMessage').html(message)
  } else {
    $('.imdb.Form-errorMessage').html('')
  }
}
