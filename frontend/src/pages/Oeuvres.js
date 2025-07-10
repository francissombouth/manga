import React, { useState, useEffect } from 'react';
import { Link as RouterLink } from 'react-router-dom';
import {
  Container,
  Grid,
  Card,
  CardContent,
  CardMedia,
  Typography,
  TextField,
  Box,
  Chip,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Pagination,
  useTheme,
  useMediaQuery,
  Skeleton,
} from '@mui/material';
import axios from 'axios';

function Oeuvres() {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
  const isTablet = useMediaQuery(theme.breakpoints.between('sm', 'md'));
  
  const [oeuvres, setOeuvres] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({
    auteur: '',
    tag: '',
    statut: '',
  });
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [auteurs, setAuteurs] = useState([]);
  const [tags, setTags] = useState([]);
  const [statuts, setStatuts] = useState([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [oeuvresRes, auteursRes, tagsRes, statutsRes] = await Promise.all([
          axios.get('/oeuvres', {
            params: {
              page,
              search,
              ...filters,
            },
          }),
          axios.get('/auteurs'),
          axios.get('/tags'),
          axios.get('/statuts'),
        ]);

        setOeuvres(oeuvresRes.data['hydra:member']);
        setTotalPages(Math.ceil(oeuvresRes.data['hydra:totalItems'] / 12));
        setAuteurs(auteursRes.data['hydra:member']);
        setTags(tagsRes.data['hydra:member']);
        setStatuts(statutsRes.data['hydra:member']);
      } catch (error) {
        setError('Erreur lors du chargement des données');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [page, search, filters]);

  const handleFilterChange = (event) => {
    const { name, value } = event.target;
    setFilters((prev) => ({
      ...prev,
      [name]: value,
    }));
    setPage(1);
  };

  const handleSearchChange = (event) => {
    setSearch(event.target.value);
    setPage(1);
  };

  const handlePageChange = (event, value) => {
    setPage(value);
    // Scroll to top on page change
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  if (loading) {
    return (
      <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
        <Box sx={{ mb: 4 }}>
          <Skeleton variant="text" width="200px" height={40} />
        </Box>
        <Grid container spacing={2} sx={{ mb: 4 }}>
          {[1, 2, 3].map((item) => (
            <Grid item xs={12} md={4} key={item}>
              <Skeleton variant="rectangular" height={56} />
            </Grid>
          ))}
        </Grid>
        <Grid container spacing={{ xs: 2, sm: 3, md: 4 }}>
          {[1, 2, 3, 4, 5, 6].map((item) => (
            <Grid item xs={12} sm={6} md={4} key={item}>
              <Skeleton variant="rectangular" height={200} />
              <Skeleton variant="text" sx={{ mt: 1 }} />
              <Skeleton variant="text" width="60%" />
            </Grid>
          ))}
        </Grid>
      </Container>
    );
  }

  if (error) {
    return (
      <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
        <Typography color="error" variant="h6" align="center">
          {error}
        </Typography>
      </Container>
    );
  }

  return (
    <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
      <Box sx={{ mb: { xs: 3, sm: 4 } }}>
        <Typography 
          variant="h4" 
          component="h1" 
          gutterBottom
          sx={{ 
            fontSize: { xs: '1.5rem', sm: '2rem', md: '2.5rem' },
            textAlign: { xs: 'center', sm: 'left' }
          }}
        >
          Œuvres
        </Typography>

        <Grid container spacing={{ xs: 2, sm: 3 }} sx={{ mb: { xs: 3, sm: 4 } }}>
          <Grid item xs={12} md={4}>
            <TextField
              fullWidth
              label="Rechercher"
              value={search}
              onChange={handleSearchChange}
              sx={{ backgroundColor: 'background.paper' }}
            />
          </Grid>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Auteur</InputLabel>
              <Select
                name="auteur"
                value={filters.auteur}
                onChange={handleFilterChange}
                label="Auteur"
                sx={{ backgroundColor: 'background.paper' }}
              >
                <MenuItem value="">Tous</MenuItem>
                {auteurs.map((auteur) => (
                  <MenuItem key={auteur.id} value={auteur.id}>
                    {auteur.nom} {auteur.prenom}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Tag</InputLabel>
              <Select
                name="tag"
                value={filters.tag}
                onChange={handleFilterChange}
                label="Tag"
                sx={{ backgroundColor: 'background.paper' }}
              >
                <MenuItem value="">Tous</MenuItem>
                {tags.map((tag) => (
                  <MenuItem key={tag.id} value={tag.id}>
                    {tag.nom}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
        </Grid>

        <Grid container spacing={{ xs: 2, sm: 3, md: 4 }}>
          {oeuvres.map((oeuvre) => (
            <Grid item key={oeuvre.id} xs={12} sm={6} md={4}>
              <Card
                component={RouterLink}
                to={`/oeuvres/${oeuvre.id}`}
                sx={{
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  textDecoration: 'none',
                  transition: 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
                  '&:hover': {
                    transform: 'translateY(-4px)',
                    boxShadow: (theme) => theme.shadows[8],
                  },
                }}
              >
                <CardMedia
                  component="img"
                  sx={{
                    height: { xs: 250, sm: 200 },
                    objectFit: 'cover',
                  }}
                  image={oeuvre.image || '/placeholder.jpg'}
                  alt={oeuvre.titre}
                />
                <CardContent sx={{ flexGrow: 1 }}>
                  <Typography 
                    gutterBottom 
                    variant="h5" 
                    component="h2"
                    sx={{ 
                      fontSize: { xs: '1.25rem', sm: '1.5rem' },
                      lineHeight: 1.3,
                      mb: 1
                    }}
                  >
                    {oeuvre.titre}
                  </Typography>
                  <Typography
                    variant="body2"
                    color="text.secondary"
                    gutterBottom
                    sx={{ mb: 1.5 }}
                  >
                    {oeuvre.auteur.nom} {oeuvre.auteur.prenom}
                  </Typography>
                  <Box sx={{ 
                    mt: 'auto',
                    display: 'flex',
                    flexWrap: 'wrap',
                    gap: 0.5
                  }}>
                    {oeuvre.tags.map((tag) => (
                      <Chip
                        key={tag.id}
                        label={tag.nom}
                        size={isMobile ? "medium" : "small"}
                        sx={{ 
                          borderRadius: '16px',
                          '&:hover': {
                            backgroundColor: 'primary.main',
                            color: 'white',
                          }
                        }}
                      />
                    ))}
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>

        {totalPages > 1 && (
          <Box
            sx={{
              display: 'flex',
              justifyContent: 'center',
              mt: { xs: 3, sm: 4 },
              mb: { xs: 2, sm: 0 }
            }}
          >
            <Pagination
              count={totalPages}
              page={page}
              onChange={handlePageChange}
              color="primary"
              size={isMobile ? "small" : "medium"}
              sx={{
                '& .MuiPaginationItem-root': {
                  fontSize: { xs: '0.875rem', sm: '1rem' }
                }
              }}
            />
          </Box>
        )}
      </Box>
    </Container>
  );
}

export default Oeuvres; 