const cheerio = require('cheerio')
const axios = require('axios')
const url = process.argv[2] ?? ''
const type = process.argv[3]
const media = process.argv[4] ?? 'movie'

async function scrape() {

    if (media === 'movie') {
        await getMovieDownloadLink(url)
    } else {
        if(type === 'latest') {
            await getSeriesLatestDownloadLink(url)
        } else {
            await getSeriesDownloadLink(url)
        }

    }
}

const getMovieDownloadLink = async (movieURL, previousMovieURL) => {
    const allMoviesURLs = await getFirstLink('.widget-body .actions', movieURL)
    const allDownloadLinks = []
    let firstMovieURL

    for (const [index, movieLink] of allMoviesURLs.entries()) {
        if (index === 0) {
            firstMovieURL = movieLink
        }

        if (movieLink === previousMovieURL) {
            break
        }

        const secondLink = await getSecondLink('#tab-5 a.link-download', movieLink)

        const thirdLink = await getThirdLink('.content a.download-link', secondLink)

        const fourthLink = await getFourthLink('.btn-loader a.link', thirdLink)

        allDownloadLinks.push(fourthLink)

        if (previousMovieURL === null || previousMovieURL === undefined) {
            break
        }
    }

    return allDownloadLinks
}


const getSeriesDownloadLink = async (seriesURL) => {

    const allMoviesURLs = await getFirstLink('.widget-body .row .col-md-auto', seriesURL)
    const allSecondLink = [];
    const allThirdLink = [];
    const allFourthLink = [];
    for (const [index, movieLink] of allMoviesURLs.entries()) {
        const secondLink = await getSecondLink('#tab-4 a.link-download', movieLink)

        allSecondLink.push(secondLink)

    }
    for (const [index, secondlink] of allSecondLink.entries()) {
        const thirdLink = await getThirdLink('.content a.download-link', secondlink)
        allThirdLink.push(thirdLink)

    }

    for (const [index, thirdlink] of allThirdLink.entries()) {
        const thirdLink = await getFourthLink('.btn-loader a.link', thirdlink)

        allFourthLink.push(thirdLink)

    }
    console.log(JSON.stringify(allFourthLink))
}

const getSeriesLatestDownloadLink = async (seriesURL, previousEpisodeURL) => {
    const allMoviesURLs = await getFirstLink('.widget-body .row .col-md-auto', seriesURL)
    const allDownloadLinks = []
    let lastSeriesURL

    for (const [index, movieLink] of allMoviesURLs.entries()) {
        if (index === 0) {
            lastSeriesURL = movieLink
        }

        if (movieLink === previousEpisodeURL) {
            break
        }

        const secondLink = await getSecondLink('#tab-4 a.link-download', movieLink)

        const thirdLink = await getThirdLink('.content a.download-link', secondLink)

        const fourthLink = await getFourthLink('.btn-loader a.link', thirdLink)

        allDownloadLinks.push(fourthLink)

        if (previousEpisodeURL === null || previousEpisodeURL === undefined) {
            break
        }
    }
    return allDownloadLinks
}

const getFirstLink = async (tags, websiteURL) => {
    // let firstLink
    const allMovies = []
    response = await axios.get(websiteURL)

    html = response.data;
    $ = cheerio.load(html);

    $(tags).each((i, element) => {
        const link = $(element).find('a').attr('href');

        allMovies.push(link)

    });

    return allMovies
}

const getSecondLink = async (tags, firstLink) => {
    let secondLink
    response = await axios.get(firstLink)
    html = response.data
    $ = cheerio.load(html)

    $(tags).each((i, element) => {
        secondLink = element.attribs.href
    })

    return secondLink
}


const getThirdLink = async (tags, secondLink) => {
    let thirdLink
    response = await axios.get(secondLink)
    html = response.data
    $ = cheerio.load(html)

    $(tags).each((i, element) => {
        thirdLink = element.attribs.href
    })

    return thirdLink
}

const getFourthLink = async (tags, thirdLink) => {
    let fourthLink
    response = await axios.get(thirdLink)
    html = response.data
    $ = cheerio.load(html)

    $(tags).each((i, element) => {
        fourthLink = element.attribs.href
    })

    return fourthLink
}
scrape();

